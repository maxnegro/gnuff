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
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');

    // Scanner page
    Route::get('/scanner', fn() => Inertia::render('Scanner'))->name('scanner');

    // Product routes
    Route::get('/product/{barcode}', [ProductController::class, 'show'])->name('product.show');
    Route::put('/product/{product}', [ProductController::class, 'update'])->name('product.update');
    Route::delete('/product/{product}', [ProductController::class, 'destroy'])->name('product.destroy');
    Route::get('/product/{product}/edit', [ProductController::class, 'edit'])->name('product.edit');


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
