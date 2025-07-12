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
                    'name' => $data['product_name'] ?? 'Prodotto sconosciuto',
                    'image_url' => $data['image_front_url'] ?? null,
                ]);
            } else {
                return response()->json(['error' => 'Prodotto non trovato'], 404);
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
}
