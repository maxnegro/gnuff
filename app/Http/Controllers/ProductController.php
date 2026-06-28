<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductImageRequest;
use App\Models\Product;
use App\Models\ProductList;
use App\Services\OpenFoodFactsService;
use App\Services\ProductImageCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProductController extends Controller
{
    private ?string $requestId = null;

    private function getRequestId(): string
    {
        if ($this->requestId) {
            return $this->requestId;
        }

        $incoming = request()->headers->get('X-Request-Id');
        $this->requestId = is_string($incoming) && $incoming !== ''
            ? $incoming
            : (string) str()->uuid();

        return $this->requestId;
    }

    private function errorResponse(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            // Compatibilità con frontend esistente che legge e.response.data.error
            'error' => $message,
            'request_id' => $this->getRequestId(),
            'details' => $details,
        ], $status);
    }

    private function productLookupErrorDetails(string $barcode, ?string $errorCode, ?int $retryAfter): array
    {
        $details = ['barcode' => $barcode];

        if ($errorCode !== null) {
            $details['error_code'] = $errorCode;
        }

        if ($retryAfter !== null && $retryAfter > 0) {
            $details['retry_after'] = $retryAfter;
        }

        return $details;
    }

    private function imageUrlRules(): array
    {
        return [
            'nullable',
            'string',
            function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }

                $valueAsString = (string) $value;
                $path = parse_url($valueAsString, PHP_URL_PATH);
                if (str_starts_with($valueAsString, '/storage/') || str_starts_with((string) $path, '/storage/')) {
                    return;
                }

                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return;
                }

                $fail('Il campo immagine deve essere un URL valido o un percorso locale /storage/.');
            },
        ];
    }

    private function resolveImageUrlForPersistence(
        ?string $incomingImageUrl,
        string $barcode,
        ProductImageCacheService $imageCacheService
    ): ?string {
        if ($incomingImageUrl === null || $incomingImageUrl === '') {
            return null;
        }

        if ($imageCacheService->isLocalStorageUrl($incomingImageUrl)) {
            return $imageCacheService->toStoragePath($incomingImageUrl);
        }

        return $imageCacheService->cacheRemoteImage($incomingImageUrl, $barcode);
    }

    /**
     * Pagina elenco prodotti con props lista attiva e tutte le liste
     */
    public function listPage(Request $request): InertiaResponse
    {
        $user = auth()->user();
        $activeListId = $request->session()->get('active_list_id');
        $owned = $user->ownedProductLists()->get();
        $shared = $user->sharedProductLists()->get();
        $all = $owned->concat($shared)->unique('id')->values();
        $activeList = $all->firstWhere('id', $activeListId) ?? $all->first();

        return Inertia::render('Product/List', [
            'active_list' => $activeList,
            'owned' => $owned,
            'shared' => $shared,
        ]);
    }

    /**
     * Restituisce tutti i prodotti (API per lista)
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 100);
        $listId = $request->input('list_id');

        if ($listId) {
            // Prendi solo i prodotti associati alla lista
            $products = ProductList::find($listId)?->products()->orderBy('name')->paginate($perPage);
            if (! $products) {
                return response()->json(['data' => []]);
            }
        } else {
            $products = Product::orderBy('name')->paginate($perPage);
        }

        return response()->json($products);
    }

    public function show(string $barcode, OpenFoodFactsService $openFoodFactsService, ProductImageCacheService $imageCacheService): JsonResponse
    {
        try {
            // Cerca prodotto nel DB
            $product = Product::where('barcode', $barcode)->first();

            // Se il prodotto ha ancora URL esterno, prova a localizzarlo in cache.
            if ($product && $product->image_url && ! $imageCacheService->isLocalStorageUrl($product->image_url)) {
                $cachedExistingImage = $imageCacheService->cacheRemoteImage($product->image_url, $barcode);
                if ($cachedExistingImage) {
                    $product->update(['image_url' => $cachedExistingImage]);
                    $product->refresh();
                }
            }

            // Chiamata centralizzata tramite service
            $apiResult = $openFoodFactsService->getProductByBarcode($barcode);
            $apiStatus = $apiResult['status'];
            $apiProduct = $apiResult['product'];
            $apiError = $apiResult['error'];
            $apiErrorCode = $apiResult['error_code'] ?? null;
            $apiRetryAfter = $apiResult['retry_after'] ?? null;

            if ($apiError && ! $product) {
                Log::warning('Product lookup failed on external source and no local fallback.', [
                    'barcode' => $barcode,
                    'request_id' => $this->getRequestId(),
                    'error' => $apiError,
                    'error_code' => $apiErrorCode,
                    'retry_after' => $apiRetryAfter,
                ]);

                return $this->errorResponse('OFF_LOOKUP_FAILED', $apiError, 502, [
                    'barcode' => $barcode,
                    'error_code' => $apiErrorCode,
                    'retry_after' => $apiRetryAfter,
                ]);
            }

            $fields = [
                'barcode' => $barcode,
                'name' => null,
                'image_url' => null,
            ];

            if ($apiProduct && is_array($apiProduct)) {
                // Prodotto con dati validi da OFF (anche status 0)
                $cachedApiImage = $imageCacheService->cacheRemoteImage(
                    $apiProduct['image_url'] ?? $apiProduct['image_front_url'] ?? null,
                    $barcode
                );
                $fields['name'] = $product?->name ?? ($apiProduct['product_name'] ?? null);
                $fields['image_url'] = $product
                    ? ($imageCacheService->toStoragePath($product->image_url) ?? null)
                    : $cachedApiImage;
                if ($product) {
                    $toUpdate = [];
                    if (! $product->name && $fields['name']) {
                        $toUpdate['name'] = $fields['name'];
                    }
                    if (! $product->image_url && $fields['image_url']) {
                        $toUpdate['image_url'] = $fields['image_url'];
                    }
                    if (! empty($toUpdate)) {
                        $product->update($toUpdate);
                    }
                } else {
                    $product = Product::create($fields);
                }
            } elseif (! $product) {
                // Prodotto non trovato né su DB né su OpenFoodFacts
                return response()->json([
                    'product' => $fields,
                    'rating' => null,
                    'not_found' => true,
                ]);
            } else {
                // Prodotto già in DB
                $fields['name'] = $product->name;
                $fields['image_url'] = $imageCacheService->toStoragePath($product->image_url) ?? null;
            }

            // Verifica se esiste già un rating per la lista attiva
            $activeListId = session('active_list_id');
            $existingRating = $product->ratings()
                ->where('product_list_id', $activeListId)
                ->first();

            return response()->json([
                'product' => $fields,
                'rating' => $existingRating?->rating,
                'rating_id' => $existingRating?->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Unhandled exception during product show flow.', [
                'barcode' => $barcode,
                'request_id' => $this->getRequestId(),
                'exception' => $e,
            ]);

            return $this->errorResponse(
                'PRODUCT_SHOW_FAILED',
                'Errore interno durante il recupero del prodotto.',
                500,
                ['barcode' => $barcode]
            );
        }
    }

    public function store(Request $request, ProductImageCacheService $imageCacheService): JsonResponse
    {
        try {
            if ($request->input('image_url') === '') {
                $request->merge(['image_url' => null]);
            }

            $validated = $request->validate([
                'barcode' => 'required',
                'name' => 'required|string|max:255',
                'image_url' => $this->imageUrlRules(),
            ]);

            $fields = ['name' => $validated['name']];
            if (array_key_exists('image_url', $validated) && $validated['image_url']) {
                $localizedImageUrl = $this->resolveImageUrlForPersistence(
                    $validated['image_url'],
                    $validated['barcode'],
                    $imageCacheService
                );

                if ($localizedImageUrl === null) {
                    Log::warning('Unable to cache product image on store.', [
                        'barcode' => $validated['barcode'],
                        'request_id' => $this->getRequestId(),
                    ]);

                    return $this->errorResponse(
                        'IMAGE_CACHE_FAILED',
                        'Impossibile scaricare e salvare localmente l\'immagine indicata.',
                        422,
                        ['barcode' => $validated['barcode']]
                    );
                }

                $fields['image_url'] = $localizedImageUrl;
            }

            $product = Product::updateOrCreate(
                ['barcode' => $validated['barcode']],
                $fields
            );

            return response()->json([
                'product' => $product,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unhandled exception during product store flow.', [
                'request_id' => $this->getRequestId(),
                'payload_barcode' => $request->input('barcode'),
                'exception' => $e,
            ]);

            return $this->errorResponse(
                'PRODUCT_STORE_FAILED',
                'Errore interno durante il salvataggio del prodotto.',
                500
            );
        }
    }

    public function edit(Product $product): InertiaResponse
    {
        return Inertia::render('Product/Edit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, string $barcode, ProductImageCacheService $imageCacheService): JsonResponse
    {
        try {
            if ($request->input('image_url') === '') {
                $request->merge(['image_url' => null]);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'image_url' => $this->imageUrlRules(),
            ]);

            $product = Product::where('barcode', $barcode)->first();
            $resolvedImageUrl = null;

            if (array_key_exists('image_url', $validated)) {
                $resolvedImageUrl = $this->resolveImageUrlForPersistence(
                    $validated['image_url'],
                    $barcode,
                    $imageCacheService
                );

                if ($validated['image_url'] && $resolvedImageUrl === null) {
                    Log::warning('Unable to cache product image on update.', [
                        'barcode' => $barcode,
                        'request_id' => $this->getRequestId(),
                    ]);

                    return $this->errorResponse(
                        'IMAGE_CACHE_FAILED',
                        'Impossibile scaricare e salvare localmente l\'immagine indicata.',
                        422,
                        ['barcode' => $barcode]
                    );
                }
            } elseif ($product) {
                $resolvedImageUrl = $product->image_url;
            }

            if (! $product) {
                // Se il prodotto non esiste, crealo
                $product = Product::create([
                    'barcode' => $barcode,
                    'name' => $validated['name'],
                    'image_url' => $resolvedImageUrl,
                ]);

                return response()->json([
                    'success' => true,
                    'created' => true,
                    'product' => $product->toArray(),
                    'message' => 'Prodotto creato con successo',
                ]);
            }

            // Aggiorna i campi esistenti
            $product->update([
                'name' => $validated['name'],
                'image_url' => $resolvedImageUrl,
            ]);
            $product->refresh();

            return response()->json([
                'success' => true,
                'created' => false,
                'product' => $product->toArray(),
                'message' => 'Prodotto aggiornato con successo',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unhandled exception during product update flow.', [
                'barcode' => $barcode,
                'request_id' => $this->getRequestId(),
                'exception' => $e,
            ]);

            return $this->errorResponse(
                'PRODUCT_UPDATE_FAILED',
                'Errore interno durante l\'aggiornamento del prodotto.',
                500,
                ['barcode' => $barcode]
            );
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function updateImage(
        string $barcode,
        UpdateProductImageRequest $request,
        ProductImageCacheService $imageCacheService
    ): JsonResponse {
        try {
            $validated = $request->validated();
            $base64Data = $validated['image_base64'];

            // Estrarre il Base64 puro (rimuovere prefisso data URI)
            $pureBase64 = preg_replace('/^data:image\/(jpeg|jpg|png|webp);base64,/i', '', $base64Data);
            $decodedImage = base64_decode($pureBase64, true);

            if ($decodedImage === false) {
                return $this->errorResponse(
                    'IMAGE_DECODE_FAILED',
                    'Impossibile decodificare l\'immagine Base64.',
                    422,
                    ['barcode' => $barcode]
                );
            }

            // Normalizzare l'immagine: rimuovere EXIF e gestire orientamento
            $normalizedImageContent = $this->normalizeImageContent($decodedImage);

            if ($normalizedImageContent === null) {
                return $this->errorResponse(
                    'IMAGE_NORMALIZATION_FAILED',
                    'Impossibile normalizzare l\'immagine. Verificare che sia un\'immagine valida.',
                    422,
                    ['barcode' => $barcode]
                );
            }

            // Salvare l'immagine normalizzata in storage
            $storagePath = $this->saveImageToStorage($normalizedImageContent, $barcode);

            if ($storagePath === null) {
                return $this->errorResponse(
                    'IMAGE_SAVE_FAILED',
                    'Impossibile salvare l\'immagine in storage.',
                    500,
                    ['barcode' => $barcode]
                );
            }

            // Aggiornare il prodotto
            $product = Product::where('barcode', $barcode)->first();

            if (! $product) {
                // Creare il prodotto se non esiste
                $product = Product::create([
                    'barcode' => $barcode,
                    'name' => "Prodotto {$barcode}",
                    'image_url' => $storagePath,
                ]);

                return response()->json([
                    'success' => true,
                    'image_url' => $storagePath,
                    'message' => 'Immagine prodotto caricata con successo.',
                ]);
            }

            // Aggiornare l'immagine del prodotto esistente
            $product->update(['image_url' => $storagePath]);

            return response()->json([
                'success' => true,
                'image_url' => $storagePath,
                'message' => 'Immagine prodotto aggiornata con successo.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unhandled exception during product image update flow.', [
                'barcode' => $barcode,
                'request_id' => $this->getRequestId(),
                'exception' => $e,
            ]);

            return $this->errorResponse(
                'PRODUCT_IMAGE_UPDATE_FAILED',
                'Errore interno durante l\'aggiornamento dell\'immagine del prodotto.',
                500,
                ['barcode' => $barcode]
            );
        }
    }

    /**
     * Normalizza il contenuto dell'immagine: rimuove EXIF e gestisce orientamento.
     * Supporta JPEG, PNG, WebP.
     */
    private function normalizeImageContent(string $imageContent): ?string
    {
        try {
            // Creare un'immagine GD dal contenuto
            $image = @imagecreatefromstring($imageContent);

            if ($image === false) {
                return null;
            }

            // Ottenere le dimensioni
            $width = imagesx($image);
            $height = imagesy($image);

            if ($width === false || $height === false || $width <= 0 || $height <= 0) {
                imagedestroy($image);

                return null;
            }

            // Creare una nuova immagine True Color (senza metadati)
            $normalized = imagecreatetruecolor($width, $height);

            if ($normalized === false) {
                imagedestroy($image);

                return null;
            }

            // Copiare i pixel (questo rimuove tutti i metadati EXIF)
            imagecopy($normalized, $image, 0, 0, 0, 0, $width, $height);

            imagedestroy($image);

            // Salvare in output buffer
            ob_start();
            imagejpeg($normalized, null, 85); // Qualità 85
            $normalizedContent = ob_get_clean();

            imagedestroy($normalized);

            return $normalizedContent !== false ? $normalizedContent : null;
        } catch (\Throwable $e) {
            Log::warning('Exception during image normalization.', [
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Salva il contenuto dell'immagine in storage pubblico.
     */
    private function saveImageToStorage(string $imageContent, string $barcode): ?string
    {
        try {
            // Determinare l'estensione basata su tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                return null;
            }

            $mimeType = finfo_buffer($finfo, $imageContent);
            finfo_close($finfo);

            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            // Costruire il percorso di storage usando lo stesso pattern di ProductImageCacheService
            $treeHash = sha1($barcode);
            $levelOne = substr($treeHash, 0, 2);
            $levelTwo = substr($treeHash, 2, 2);
            // Usare timestamp per differenziare i caricamenti
            $fileHash = substr(sha1(time().$barcode.random_int(0, PHP_INT_MAX)), 0, 16);

            $path = "products/{$levelOne}/{$levelTwo}/{$barcode}-{$fileHash}.{$extension}";

            // Salvare il file in storage pubblico
            Storage::disk('public')->put($path, $imageContent);

            // Restituire l'URL pubblico
            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            Log::error('Exception while saving image to storage.', [
                'barcode' => $barcode,
                'exception' => $e,
            ]);

            return null;
        }
    }
}
