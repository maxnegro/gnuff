<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    public function show($barcode)
    {
        // Memorizza utente corrente
        $user = auth()->user();

        // Cerca prodotto nel DB
        $product = Product::where('barcode', $barcode)->first();

        // Se non esiste, tenta di recuperarlo da Open Food Facts
        if (!$product) {
            // Prova a recuperarlo da OpenFoodFacts
            $response = Http::get("https://world.openfoodfacts.org/api/v0/product/{$barcode}.json");
        
            if ($response->successful() && $response['status'] == 1) {
                $data = $response['product'];
        
                $product = Product::create([
                    'barcode' => $barcode,
                    'name' => $data['product_name'] ?? null,
                    'image_url' => $data['image_front_url'] ?? null,
                ]);
            } else {
                // return response()->json(['error' => 'Errore nel collegamento a OpenFoodFacts'], 404);
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

}
