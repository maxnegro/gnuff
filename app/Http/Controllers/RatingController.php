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
                'image_url' => 'https://placehold.co/640x480.png?text=Prodotto',
            ]
        );

        $activeListId = session('active_list_id');
        if (!$activeListId) {
            return response()->json(['message' => 'Nessuna lista attiva selezionata'], 422);
        }
        $rating = Rating::updateOrCreate(
            [
                'product_list_id' => $activeListId,
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
        $activeListId = session('active_list_id');
        if (!$activeListId) {
            return response()->json([]); // Nessuna lista attiva selezionata
        }
        $ratings = \App\Models\Rating::where('product_list_id', $activeListId)
            ->with(['product', 'productList'])
            ->latest()
            ->take(10)
            ->get();

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
        $listId = $request->input('list_id') ?? session('active_list_id');
        $query = Rating::with('product');
        if ($listId) {
            $query->where('product_list_id', $listId);
        }
        $ratings = $query->latest()->paginate($perPage);
        return response()->json($ratings);
    }
}
