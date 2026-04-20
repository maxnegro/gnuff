<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RatingController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {

        // API: lista attiva e tutte le liste dell'utente
        Route::get('/lists/active-and-all', [\App\Http\Controllers\ProductListController::class, 'activeAndAll']);
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, '__invoke'])->name('dashboard');

    // Scanner page
    Route::get('/scanner', fn() => Inertia::render('Scanner'))->name('scanner');

    // Product routes
    Route::get('/products', [\App\Http\Controllers\ProductController::class, 'listPage'])->name('product.list');
    Route::get('/product/{barcode}', [ProductController::class, 'show'])->name('product.show');
    Route::put('/product/{product}', [ProductController::class, 'update'])->name('product.update');
    Route::delete('/product/{product}', [ProductController::class, 'destroy'])->name('product.destroy');
    Route::get('/product/{product}/edit', [ProductController::class, 'edit'])->name('product.edit');

    // ProductList routes
    Route::get('/lists', [\App\Http\Controllers\ProductListController::class, 'index'])->name('lists.index');
    Route::post('/lists', [\App\Http\Controllers\ProductListController::class, 'store'])->name('lists.store');
    Route::put('/lists/{productList}', [\App\Http\Controllers\ProductListController::class, 'update'])->name('lists.update');
    Route::delete('/lists/{productList}', [\App\Http\Controllers\ProductListController::class, 'destroy'])->name('lists.destroy');
    Route::post('/lists/{productList}/invite', [\App\Http\Controllers\ProductListController::class, 'invite'])->name('lists.invite');
    Route::post('/lists/{productList}/accept', [\App\Http\Controllers\ProductListController::class, 'acceptInvite'])->name('lists.accept');
    Route::post('/lists/{productList}/decline', [\App\Http\Controllers\ProductListController::class, 'declineInvite'])->name('lists.decline');

    // Imposta la lista attiva per la sessione
    Route::post('/lists/{productList}/active', [\App\Http\Controllers\ProductListController::class, 'setActive'])->name('lists.setActive');


    // Save product name
    Route::post('/product', [ProductController::class, 'store'])->name('product.store');

    // Submit rating
    Route::post('/rate', [RatingController::class, 'store'])->name('rating.store');

    // Get latest ratings
    Route::get('/user/ratings', [RatingController::class, 'userRatings']);

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
