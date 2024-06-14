<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user/{id}', [AuthController::class, 'getUserById'])->middleware('auth:sanctum');
Route::post('/user/{id}', [AuthController::class, 'updateUser'])->middleware('auth:sanctum');
Route::get('/restaurants', [AuthController::class, 'getAllRestaurants'])->middleware('auth:sanctum');
Route::get('/restaurants/{userId}/products', [ProductController::class, 'getProductsByRestaurant'])->middleware('auth:sanctum');
Route::post('/restaurants/{userId}/products', [ProductController::class, 'addProduct'])->middleware('auth:sanctum');
Route::post('/products/{productId}', [ProductController::class, 'updateProduct'])->middleware('auth:sanctum');
Route::delete('/products/{productId}', [ProductController::class, 'deleteProduct'])->middleware('auth:sanctum');
