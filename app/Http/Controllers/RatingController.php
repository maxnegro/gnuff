<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RatingController extends Controller
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
            // Compatibilita con frontend esistente che legge e.response.data.error
            'error' => $message,
            'request_id' => $this->getRequestId(),
            'details' => $details,
        ], $status);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'barcode' => 'required|string',
                'value' => 'required|in:gnuf,ok,meh,bleah',
            ]);

            $product = Product::firstOrCreate(
                ['barcode' => $request->barcode],
                [
                    'name' => 'Prodotto sconosciuto',
                    'image_url' => 'https://placehold.co/640x480.png?text=Prodotto',
                ]
            );

            $activeListId = session('active_list_id');
            if (! $activeListId) {
                return $this->errorResponse(
                    'ACTIVE_LIST_MISSING',
                    'Nessuna lista attiva selezionata',
                    422
                );
            }

            $rating = Rating::updateOrCreate(
                [
                    'product_list_id' => $activeListId,
                    'product_id' => $product->id,
                ],
                [
                    'rating' => $request->value,
                    'author_name' => $request->user()?->name,
                ]
            );

            return response()->json(['message' => 'Valutazione salvata', 'rating' => $rating]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unhandled exception during rating store flow.', [
                'request_id' => $this->getRequestId(),
                'barcode' => $request->input('barcode'),
                'exception' => $e,
            ]);

            return $this->errorResponse(
                'RATING_STORE_FAILED',
                'Errore interno durante il salvataggio della valutazione.',
                500
            );
        }
    }

    public function userRatings(): JsonResponse
    {
        $activeListId = session('active_list_id');
        if (! $activeListId) {
            return response()->json([]); // Nessuna lista attiva selezionata
        }

        $ratings = Rating::where('product_list_id', $activeListId)
            ->with(['product', 'productList'])
            ->latest()
            ->take(10)
            ->get();

        return response()->json($ratings);
    }

    /**
     * Aggiorna un rating esistente
     */
    public function update(Request $request, Rating $rating): JsonResponse
    {
        try {
            // Autorizzazione opzionale: aggiungi qui se usi policy
            $request->validate([
                'value' => 'required|in:gnuf,ok,meh,bleah',
            ]);
            $rating->rating = $request->value;
            $rating->author_name = $request->user()?->name;
            $rating->save();

            return response()->json(['message' => 'Valutazione aggiornata', 'rating' => $rating]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unhandled exception during rating update flow.', [
                'request_id' => $this->getRequestId(),
                'rating_id' => $rating->id,
                'exception' => $e,
            ]);

            return $this->errorResponse(
                'RATING_UPDATE_FAILED',
                'Errore interno durante l\'aggiornamento della valutazione.',
                500,
                ['rating_id' => $rating->id]
            );
        }
    }

    /**
     * Elimina un rating esistente
     */
    public function destroy(Rating $rating): JsonResponse
    {
        try {
            // Autorizzazione opzionale: aggiungi qui se usi policy
            $rating->delete();

            return response()->json(['message' => 'Valutazione eliminata']);
        } catch (\Throwable $e) {
            Log::error('Unhandled exception during rating destroy flow.', [
                'request_id' => $this->getRequestId(),
                'rating_id' => $rating->id,
                'exception' => $e,
            ]);

            return $this->errorResponse(
                'RATING_DELETE_FAILED',
                'Errore interno durante l\'eliminazione della valutazione.',
                500,
                ['rating_id' => $rating->id]
            );
        }
    }

    /**
     * Restituisce una lista paginata di rating
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $listId = $request->input('list_id') ?? session('active_list_id');
        $query = Rating::with('product');

        if ($listId) {
            $query->where('product_list_id', $listId);
        }

        $ratings = $query->latest()->paginate($perPage);

        return response()->json($ratings);
    }
}
