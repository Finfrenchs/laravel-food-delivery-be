<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// User and Auth
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/userRegister', [AuthController::class, 'userRegister']);
Route::post('/restaurantRegister', [AuthController::class, 'restaurantRegister']);
Route::post('/driverRegister', [AuthController::class, 'driverRegister']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user/{id}', [AuthController::class, 'getUserById'])->middleware('auth:sanctum');
Route::post('/userUpdate', [AuthController::class, 'updateUser'])->middleware('auth:sanctum');
Route::get('/restaurants', [AuthController::class, 'getAllRestaurants'])->middleware('auth:sanctum');
Route::post('/update-fcm', [AuthController::class, 'updateFcmId'])->middleware('auth:sanctum');
Route::patch('/update-latlong', [AuthController::class, 'updateLatLong'])->middleware('auth:sanctum');

// Product
Route::get('/restaurants/{userId}/products', [ProductController::class, 'getProductsByRestaurant'])->middleware('auth:sanctum');
Route::get('/products', [ProductController::class, 'getProductsByUserId'])->middleware('auth:sanctum');
Route::post('/products', [ProductController::class, 'addProduct'])->middleware('auth:sanctum');
Route::post('/products/{productId}', [ProductController::class, 'updateProduct'])->middleware('auth:sanctum');
Route::delete('/products/{productId}', [ProductController::class, 'deleteProduct'])->middleware('auth:sanctum');

// Order
Route::post('/order/createOrder', [OrderController::class, 'createOrder'])->middleware('auth:sanctum');
Route::post('/order/purchase/{orderId}', [OrderController::class, 'purchaseOrder'])->middleware('auth:sanctum');
Route::get('/order/history', [OrderController::class, 'orderHistory'])->middleware('auth:sanctum');
Route::get('/order/history/{orderId}', [OrderController::class, 'orderDetail'])->middleware('auth:sanctum');
Route::post('/order/cancel/{orderId}', [OrderController::class, 'cancelOrder'])->middleware('auth:sanctum');
Route::get('/payment-methods', [OrderController::class, 'getPaymentMethods'])->middleware('auth:sanctum');
Route::post('/xendit-callback', [OrderController::class, 'webhook']);

Route::get('/restaurant/orders', [OrderController::class, 'getOrdersByRestaurant'])->middleware('auth:sanctum');
Route::post('/restaurant/order/{orderId}/prepare', [OrderController::class, 'prepareOrder'])->middleware('auth:sanctum');
Route::post('/restaurant/order/{orderId}/ready', [OrderController::class, 'markOrderAsReady'])->middleware('auth:sanctum');
Route::get('/restaurant/report', [OrderController::class, 'getRestaurantReport'])->middleware('auth:sanctum');

Route::get('/driver/orders/waiting', [OrderController::class, 'getOrdersWaitingPickup'])->middleware('auth:sanctum');
Route::post('/driver/order/take/{orderId}', [OrderController::class, 'takeDelivery'])->middleware('auth:sanctum');
Route::post('/driver/order/done/{orderId}', [OrderController::class, 'markOrderAsDone'])->middleware('auth:sanctum');
Route::get('/driver/order/{orderId}', [OrderController::class, 'getOrderDetailById'])->middleware('auth:sanctum');
Route::get('/driver/orders', [OrderController::class, 'getDriverOrders'])->middleware('auth:sanctum');
