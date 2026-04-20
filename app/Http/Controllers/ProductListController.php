<?php

namespace App\Http\Controllers;

use App\Models\ProductList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductListController extends Controller {

        // API: restituisce lista attiva e tutte le liste dell'utente
        public function activeAndAll(Request $request)
    {
        $user = Auth::user();
        $owned = $user->ownedProductLists()->get();
        $shared = $user->sharedProductLists()->get();
        $all = $owned->concat($shared)->unique('id')->values();

        // Ordine di priorità:
        // 1. selected_list_id se accessibile
        // 2. lista "Default" se accessibile
        // 3. prima lista disponibile

        $active = null;
        if ($user->selected_list_id) {
            $active = $all->firstWhere('id', $user->selected_list_id);
        }
        if (!$active) {
            $active = $all->firstWhere(fn($l) => strtolower($l->name) === 'default');
        }
        if (!$active) {
            $active = $all->first();
        }

        // Aggiorna la sessione se necessario
        if ($active) {
            $request->session()->put('active_list_id', $active->id);
        }

        return response()->json([
            'active' => $active,
            'all' => $all,
        ]);
    }
    // Imposta la lista attiva nella sessione
    public function setActive(Request $request, ProductList $productList)
    {
        // Carica la relazione users per evitare problemi di lazy loading
        $productList->load('users');
        $user = Auth::user();
        $userId = $user->id;
        $isOwner = $productList->owner_id == $userId;
        $isMember = $productList->users->contains($userId);
        if (!$isOwner && !$isMember) {
            abort(403, 'Non autorizzato');
        }
        $request->session()->put('active_list_id', $productList->id);
        // Persisti la selezione anche dopo il logout
        $user->selected_list_id = $productList->id;
        $user->save();
        return redirect()->back()->with('success', 'Lista attiva aggiornata');
    }

    public function index()
    {
        $user = Auth::user();
        $owned = $user->ownedProductLists()->with(['users', 'products'])->get();
        $shared = $user->sharedProductLists()->with(['users', 'products', 'owner'])->get();
        $all = $owned->concat($shared)->unique('id')->values();
        $active = null;
        if ($user->selected_list_id) {
            $active = $all->firstWhere('id', $user->selected_list_id);
        }
        if (!$active) {
            $active = $all->firstWhere(fn($l) => strtolower($l->name) === 'default');
        }
        if (!$active) {
            $active = $all->first();
        }
        $invitations = []; // Da implementare: inviti ricevuti
        return Inertia::render('ProductList/Index', [
            'owned' => $owned,
            'shared' => $shared,
            'active_list' => $active,
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
