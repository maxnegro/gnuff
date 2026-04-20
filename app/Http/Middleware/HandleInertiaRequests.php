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
            'active_list' => fn () => $request->user() && $request->session()->get('active_list_id')
                ? ($request->user()->ownedProductLists->concat($request->user()->sharedProductLists)->firstWhere('id', $request->session()->get('active_list_id')))
                : null,
        ];
    }
}
