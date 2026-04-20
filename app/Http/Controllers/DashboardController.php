<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ProductList;
use App\Models\Rating;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $activeListId = $request->input('active_list_id') ?? $request->session()->get('active_list_id');

        $owned = $user->ownedProductLists;
        $shared = $user->sharedProductLists;
        $all = $owned->merge($shared)->unique('id')->values();

        // Determina la lista attiva
        $active_list = null;
        if ($activeListId) {
            $active_list = $all->firstWhere('id', $activeListId);
        }
        if (!$active_list && $all->count()) {
            $active_list = $all->first();
        }

        // Recupera le valutazioni filtrate per la lista attiva (se presente)
        $ratings = collect();
        if ($active_list) {
            $ratings = Rating::with(['product', 'productList'])
                ->where('product_list_id', $active_list->id)
                ->whereHas('productList', function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                        ->orWhereHas('users', function ($q2) use ($user) {
                            $q2->where('user_id', $user->id);
                        });
                })
                ->orderByDesc('updated_at')
                ->take(10)
                ->get();
        }

        return Inertia::render('Dashboard', [
            'ratings' => $ratings,
            'active_list' => $active_list,
            'owned' => $owned,
            'shared' => $shared,
        ]);
    }
}
