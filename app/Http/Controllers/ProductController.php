<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\OpenFoodFactsService;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Restituisce tutti i prodotti (API per lista)
     */
    public function apiIndex(Request $request)
    {
        $perPage = $request->input('per_page', 100);
        $products = Product::orderBy('name')->paginate($perPage);
        return response()->json($products);
    }
    public function show($barcode, OpenFoodFactsService $openFoodFactsService)
    {
        // Memorizza utente corrente
        $user = auth()->user();

        // Cerca prodotto nel DB
        $product = Product::where('barcode', $barcode)->first();

        // Chiamata centralizzata tramite service
        $apiResult = $openFoodFactsService->getProductByBarcode($barcode);
        $apiStatus = $apiResult['status'];
        $apiProduct = $apiResult['product'];
        $apiError = $apiResult['error'];

        if ($apiError && !$product) {
            return response()->json([
                'error' => $apiError,
                'product' => null,
                'rating' => null,
            ], 502);
        }

        $fields = [
            'barcode' => $barcode,
            'name' => null,
            'image_url' => null,
        ];

        if ($apiStatus === 1 && $apiProduct) {
            // Prodotto trovato su OpenFoodFacts
            $fields['name'] = $apiProduct['product_name'] ?? null;
            // image_url può essere image_url o image_front_url
            $fields['image_url'] = $apiProduct['image_url'] ?? $apiProduct['image_front_url'] ?? null;
            // Aggiorna/crea in DB se serve
            if ($product) {
                if ($product->name !== $fields['name'] || $product->image_url !== $fields['image_url']) {
                    $product->update($fields);
                }
            } else {
                $product = Product::create($fields);
            }
        } elseif ($apiStatus === 0 && !$product) {
            // Prodotto non trovato né su DB né su OpenFoodFacts
            return response()->json([
                'product' => $fields,
                'rating' => null,
                'not_found' => true,
            ]);
        } elseif (!$product) {
            // Errore generico o risposta inattesa
            return response()->json([
                'product' => $fields,
                'rating' => null,
                'error' => $apiError ?? 'Errore nella risposta da OpenFoodFacts',
            ], 502);
        } else {
            // Prodotto già in DB
            $fields['name'] = $product->name;
            $fields['image_url'] = $product->image_url;
        }


        // Verifica se esiste già un rating per la lista attiva
        $activeListId = session('active_list_id');
        $existingRating = $product->ratings()
            ->where('product_list_id', $activeListId)
            ->first();

        return response()->json([
            'product' => $fields,
            'rating' => $existingRating?->rating,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'barcode' => 'required',
            'name' => 'required|string|max:255',
        ]);


        $fields = [ 'name' => $request->name ];
        if ($request->filled('image_url')) {
            $fields['image_url'] = $request->image_url;
        }
        $product = Product::updateOrCreate(
            [ 'barcode' => $request->barcode ],
            $fields
        );

        return response()->json([
            'product' => $product,
        ]);
    }

    public function edit(Product $product)
    {
        return Inertia::render('Product/Edit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|url',
        ]);

        $product->update($request->only(['name', 'image']));

        return redirect()->route('dashboard')->with('success', 'Prodotto aggiornato');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('dashboard')->with('success', 'Prodotto eliminato');
    }


}
