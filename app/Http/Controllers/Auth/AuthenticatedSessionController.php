<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Imposta la lista attiva in sessione dopo login
        $user = Auth::user();
        $owned = $user->ownedProductLists()->get();
        $shared = $user->sharedProductLists()->get();
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
        if ($active) {
            $request->session()->put('active_list_id', $active->id);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
