<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
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
    public function show($barcode)
    {
        // Memorizza utente corrente
        $user = auth()->user();

        // Cerca prodotto nel DB
        $product = Product::where('barcode', $barcode)->first();

        // Se non esiste, tenta di recuperarlo da Open Food Facts (solo v2)
        if (!$product) {
            $response = Http::get("https://world.openfoodfacts.org/api/v2/product/{$barcode}.json");
            $data = null;
            if ($response->successful() && isset($response['product']) && !empty($response['product'])) {
                $data = $response['product'];
            }
            if ($data) {
                $product = Product::create([
                    'barcode' => $barcode,
                    'name' => $data['product_name'] ?? null,
                    'image_url' => $data['image_front_url'] ?? null,
                ]);
            } else {
                return response()->json([
                    'product' => [
                        'barcode' => $barcode,
                        'name' => null,
                        'image_url' => null,
                    ],
                    'rating' => null,
                ]);
            }
        }

        // Verifica se l’utente ha già inserito un rating
        $existingRating = $product->ratings()
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'product' => $product,
            'rating' => $existingRating?->rating,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'barcode' => 'required',
            'name' => 'required|string|max:255',
        ]);

        $product = Product::updateOrCreate(
            [ 'barcode' => $request->barcode ],
            [ 'name' => $request->name, 'image_url' => null ]
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
