<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // Props globali per tutte le pagine
            'owned' => fn () => $request->user()
                ? $request->user()->ownedProductLists()->with(['users', 'products'])->get()
                : [],
            'shared' => fn () => $request->user()
                ? $request->user()->sharedProductLists()->with(['users', 'products', 'owner'])->get()
                : [],
            'active_list' => function () use ($request) {
                if (! $request->user()) {
                    return null;
                }

                $all = $request->user()->ownedProductLists->concat($request->user()->sharedProductLists);
                $sessionListId = $request->session()->get('active_list_id');
                $active = $sessionListId ? $all->firstWhere('id', $sessionListId) : null;

                // Auto-inizializza la sessione se active_list_id non è impostata o non è più valida
                if (! $active) {
                    $active = $all->firstWhere('id', $request->user()->selected_list_id)
                        ?? $all->firstWhere(fn ($l) => strtolower($l->name) === 'default')
                        ?? $all->first();

                    if ($active) {
                        $request->session()->put('active_list_id', $active->id);
                    }
                }

                return $active;
            },
        ];
    }
}
