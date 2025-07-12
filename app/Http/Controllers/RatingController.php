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
            ['name' => 'Prodotto sconosciuto']
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

}
