<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    Route::get('/lists/active-and-all', [ProductListController::class, 'activeAndAll']);
    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');

    // Scanner page
    Route::get('/scanner', fn () => Inertia::render('Scanner'))->name('scanner');

    // Product routes
    Route::get('/products', [ProductController::class, 'listPage'])->name('product.list');
    Route::get('/product/{barcode}', [ProductController::class, 'show'])->name('product.show');
    Route::put('/product/{barcode}', [ProductController::class, 'update'])->name('product.update');
    Route::delete('/product/{product}', [ProductController::class, 'destroy'])->name('product.destroy');
    Route::get('/product/{product}/edit', [ProductController::class, 'edit'])->name('product.edit');
    Route::post('/product/{barcode}/image', [ProductController::class, 'updateImage'])->name('product.updateImage');

    // ProductList routes
    Route::get('/lists', [ProductListController::class, 'index'])->name('lists.index');
    Route::post('/lists', [ProductListController::class, 'store'])->name('lists.store');
    Route::put('/lists/{productList}', [ProductListController::class, 'update'])->name('lists.update');
    Route::delete('/lists/{productList}', [ProductListController::class, 'destroy'])->name('lists.destroy');
    Route::post('/lists/{productList}/invite', [ProductListController::class, 'invite'])->name('lists.invite');
    Route::post('/lists/{productList}/accept', [ProductListController::class, 'acceptInvite'])->name('lists.accept');
    Route::post('/lists/{productList}/decline', [ProductListController::class, 'declineInvite'])->name('lists.decline');

    // Imposta la lista attiva per la sessione
    Route::post('/lists/{productList}/active', [ProductListController::class, 'setActive'])->name('lists.setActive');

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
