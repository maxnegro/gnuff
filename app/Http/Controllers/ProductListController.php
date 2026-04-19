<?php

namespace App\Http\Controllers;

use App\Models\ProductList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductListController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $owned = $user->ownedProductLists()->with(['users', 'products'])->get();
        $shared = $user->sharedProductLists()->with(['users', 'products', 'owner'])->get();
        $invitations = []; // Da implementare: inviti ricevuti
        return Inertia::render('ProductList/Index', [
            'owned' => $owned,
            'shared' => $shared,
            'invitations' => $invitations,
            'user' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $list = ProductList::create([
            'name' => $request->name,
            'owner_id' => Auth::id(),
        ]);
        $list->users()->attach(Auth::id());
        return redirect()->back();
    }

    public function update(Request $request, ProductList $productList)
    {
        $this->authorize('update', $productList);
        $request->validate(['name' => 'required|string|max:255']);
        $productList->update(['name' => $request->name]);
        return redirect()->back();
    }

    public function destroy(ProductList $productList)
    {
        $this->authorize('delete', $productList);
        $productList->delete();
        return redirect()->back();
    }

    // Invito utente (mock, da implementare logica inviti reali)
    public function invite(Request $request, ProductList $productList)
    {
        $this->authorize('update', $productList);
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['email' => 'Utente non trovato']);
        }
        // Qui andrebbe creata una entry invito, per ora aggiunge direttamente
        $productList->users()->syncWithoutDetaching($user->id);
        return redirect()->back();
    }

    // Accetta invito (mock)
    public function acceptInvite(Request $request, ProductList $productList)
    {
        $productList->users()->syncWithoutDetaching(Auth::id());
        return redirect()->back();
    }

    // Rifiuta invito (mock)
    public function declineInvite(Request $request, ProductList $productList)
    {
        // Qui si eliminerebbe l'invito
        return redirect()->back();
    }
}
