<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController; 
use App\Http\Controllers\Api\OrderController; 
use App\Http\Controllers\Api\StaffController; 
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\PlanController; 
use App\Http\Controllers\Api\ClientSubscriptionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes for authentication
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Routes protected by auth:sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::put('/profile/device-token', [ProfileController::class, 'updateDeviceToken']);

    // Service Routes
    Route::apiResource('services', ServiceController::class);

     // Order Routes
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']); 
    Route::put('/orders/{order}/assign-staff', [OrderController::class, 'assignStaff']); 

    // Staff Management Routes (for vendors)
    Route::post('/staffs', [StaffController::class, 'store']);
    Route::get('/staffs', [StaffController::class, 'index']);
    Route::delete('/staffs/{staff}', [StaffController::class, 'destroy']);

    // Subscription Plan Routes (for vendors to manage)
    Route::apiResource('plans', PlanController::class);

    // Vendor routes
    Route::get('/vendors', [VendorController::class, 'index']);
    Route::post('/vendors', [VendorController::class, 'store']);
    Route::get('/vendors/{vendor}', [VendorController::class, 'show']);    // <-- Add this
    Route::put('/vendors/{vendor}', [VendorController::class, 'update']);   // <-- Add this
    Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy']); // <-- Add this

    // Ratings & Reviews Route
    Route::post('/orders/{order}/ratings', [RatingController::class, 'store']);

    // Client Subscription Routes
    Route::post('/subscriptions', [ClientSubscriptionController::class, 'store']);
    Route::get('/subscriptions', [ClientSubscriptionController::class, 'index']);

    // Payment Routes
    Route::post('/payments/initiate', [PaymentController::class, 'initiatePayment']);
    Route::post('/payments/verify', [PaymentController::class, 'verifyPayment']);
    Route::post('/payments/initiate-subscription', [PaymentController::class, 'initiateSubscriptionPayment']);

});

    Route::get('/public/plans', [PlanController::class, 'getPublicPlans']); 
