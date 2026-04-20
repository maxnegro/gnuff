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

        // Recupera tutte le liste dell'utente (proprietario o condivise)
        $allLists = $user->ownedProductLists->merge($user->sharedProductLists);

        // Determina la lista attiva
        $activeList = null;
        if ($activeListId) {
            $activeList = $allLists->firstWhere('id', $activeListId);
        }
        if (!$activeList && $allLists->count()) {
            $activeList = $allLists->first();
        }

        // Recupera le valutazioni filtrate per la lista attiva (se presente)
        $ratings = collect();
        if ($activeList) {
            $ratings = Rating::with(['product', 'productList'])
                ->where('product_list_id', $activeList->id)
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
            'activeList' => $activeList,
            'allLists' => $allLists,
        ]);
    }
}
