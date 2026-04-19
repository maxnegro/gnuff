<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Rating;
use App\Models\Product;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
            'value' => 'required|in:gnuf,ok,meh,bleah',
        ]);

        $product = Product::firstOrCreate(
            ['barcode' => $request->barcode],
            [
                'name' => 'Prodotto sconosciuto',
                'image_url' => 'https://via.placeholder.com/640x480.png?text=Prodotto',
            ]
        );

        $rating = Rating::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'product_id' => $product->id,
            ],
            [
                'rating' => $request->value,
            ]
        );

        return response()->json(['message' => 'Valutazione salvata', 'rating' => $rating]);
    }

    public function userRatings()
    {
        $ratings = auth()->user()->ratings()->with('product')->latest()->take(10)->get();

        return response()->json($ratings);
    }

    /**
     * Aggiorna un rating esistente
     */
    public function update(Request $request, Rating $rating)
    {
        // Autorizzazione opzionale: aggiungi qui se usi policy
        $request->validate([
            'value' => 'required|in:gnuf,ok,meh,bleah',
        ]);
        $rating->rating = $request->value;
        $rating->save();
        return response()->json(['message' => 'Valutazione aggiornata', 'rating' => $rating]);
    }

    /**
     * Elimina un rating esistente
     */
    public function destroy(Rating $rating)
    {
        // Autorizzazione opzionale: aggiungi qui se usi policy
        $rating->delete();
        return response()->json(['message' => 'Valutazione eliminata']);
    }

    /**
     * Restituisce una lista paginata di rating
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $ratings = Rating::with(['user', 'product'])->latest()->paginate($perPage);
        return response()->json($ratings);
    }
}
