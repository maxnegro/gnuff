<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RatingController;

Route::middleware([
    'auth:sanctum',
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
])->group(function () {
    Route::post('/rate', [RatingController::class, 'store']);
    Route::put('/rate/{rating}', [RatingController::class, 'update']);
    Route::delete('/rate/{rating}', [RatingController::class, 'destroy']);
    Route::get('/user/ratings', [RatingController::class, 'userRatings']);
    Route::get('/ratings', [RatingController::class, 'index']); // paginazione/listing
    Route::get('/products', [\App\Http\Controllers\ProductController::class, 'apiIndex']); // lista prodotti
});
