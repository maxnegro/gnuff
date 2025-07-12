<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
            'rating' => 'required|in:gnuf,ok,meh,bleah',
        ]);

        $product = \App\Models\Product::firstOrCreate(['barcode' => $validated['barcode']]);

        $rating = Rating::updateOrCreate(
            ['user_id' => $request->user()->id, 'product_id' => $product->id],
            ['rating' => $validated['rating']]
        );

        return response()->json(['success' => true, 'data' => $rating]);
    }
}
