<?php

namespace App\Http\Controllers;

use App\Models\ProductList;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProductListController extends Controller
{
    private ?string $requestId = null;

    private function getRequestId(): string
    {
        if ($this->requestId) {
            return $this->requestId;
        }

        $incoming = request()->headers->get('X-Request-Id');
        $this->requestId = is_string($incoming) && $incoming !== ''
            ? $incoming
            : (string) str()->uuid();

        return $this->requestId;
    }

    private function errorResponse(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'error' => $message,
            'request_id' => $this->getRequestId(),
            'details' => $details,
        ], $status);
    }

    private function resolveActiveList(Collection $all, ?int $selectedListId): ?ProductList
    {
        if ($selectedListId) {
            $active = $all->firstWhere('id', $selectedListId);
            if ($active) {
                return $active;
            }
        }

        $active = $all->firstWhere(fn ($list) => strtolower($list->name) === 'default');
        if ($active) {
            return $active;
        }

        return $all->first();
    }

    // API: restituisce lista attiva e tutte le liste dell'utente
    public function activeAndAll(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $owned = $user->ownedProductLists()->get();
            $shared = $user->sharedProductLists()->get();
            $all = $owned->concat($shared)->unique('id')->values();
            $active = $this->resolveActiveList($all, $user->selected_list_id);

            // Aggiorna la sessione se necessario
            if ($active) {
                $request->session()->put('active_list_id', $active->id);
            }

            return response()->json([
                'active' => $active,
                'all' => $all,
            ]);
        } catch (\Throwable $e) {
            Log::error('Unhandled exception during product lists active-and-all flow.', [
                'request_id' => $this->getRequestId(),
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return $this->errorResponse(
                'LISTS_FETCH_FAILED',
                'Errore interno durante il recupero delle liste.',
                500
            );
        }
    }

    // Imposta la lista attiva nella sessione
    public function setActive(Request $request, ProductList $productList): RedirectResponse
    {
        // Carica la relazione users per evitare problemi di lazy loading
        $productList->load('users');
        $user = Auth::user();
        $userId = $user->id;
        $isOwner = $productList->owner_id == $userId;
        $isMember = $productList->users->contains($userId);
        if (! $isOwner && ! $isMember) {
            abort(403, 'Non autorizzato');
        }
        $request->session()->put('active_list_id', $productList->id);
        // Persisti la selezione anche dopo il logout
        $user->selected_list_id = $productList->id;
        $user->save();

        return redirect()->back()->with('success', 'Lista attiva aggiornata');
    }

    public function index(): InertiaResponse
    {
        $user = Auth::user();
        $owned = $user->ownedProductLists()->with(['users', 'products'])->get();
        $shared = $user->sharedProductLists()->with(['users', 'products', 'owner'])->get();
        $all = $owned->concat($shared)->unique('id')->values();
        $active = $this->resolveActiveList($all, $user->selected_list_id);
        $invitations = []; // Da implementare: inviti ricevuti

        return Inertia::render('ProductList/Index', [
            'owned' => $owned,
            'shared' => $shared,
            'active_list' => $active,
            'invitations' => $invitations,
            'user' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $list = ProductList::create([
            'name' => $request->name,
            'owner_id' => Auth::id(),
        ]);
        $list->users()->attach(Auth::id());

        return redirect()->back();
    }

    public function update(Request $request, ProductList $productList): RedirectResponse
    {
        $this->authorize('update', $productList);
        $request->validate(['name' => 'required|string|max:255']);
        $productList->update(['name' => $request->name]);

        return redirect()->back();
    }

    public function destroy(ProductList $productList): RedirectResponse
    {
        $this->authorize('delete', $productList);
        $productList->delete();

        return redirect()->back();
    }

    // Invito utente (mock, da implementare logica inviti reali)
    public function invite(Request $request, ProductList $productList): RedirectResponse
    {
        $this->authorize('update', $productList);
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return redirect()->back()->withErrors(['email' => 'Utente non trovato']);
        }
        // Qui andrebbe creata una entry invito, per ora aggiunge direttamente
        $productList->users()->syncWithoutDetaching($user->id);

        return redirect()->back();
    }

    // Accetta invito (mock)
    public function acceptInvite(Request $request, ProductList $productList): RedirectResponse
    {
        $productList->users()->syncWithoutDetaching(Auth::id());

        return redirect()->back();
    }

    // Rifiuta invito (mock)
    public function declineInvite(Request $request, ProductList $productList): RedirectResponse
    {
        // Qui si eliminerebbe l'invito
        return redirect()->back();
    }
}
