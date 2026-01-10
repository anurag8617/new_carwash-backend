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
use App\Http\Controllers\Api\StaffAndVendorAdminController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\VendorDashboardController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AdminVendorController;
use App\Http\Controllers\Api\NotificationController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('/banners', [BannerController::class, 'getActiveBanners']);
Route::get('/public/plans', [PlanController::class, 'getPublicPlans']);
Route::get('/client/vendors', [VendorController::class, 'search']);
Route::get('/client/vendors/{id}', [VendorController::class, 'showPublic']);

    
// ✅ NEW: Public route for clients to browse services
Route::get('/client/services', [ServiceController::class, 'getPublicServices']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);

    // ✅ FIXED: User Profile Routes (This fixes the 404 error)
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/device-token', [ProfileController::class, 'updateDeviceToken']);

    // Get authenticated user (optional, can be removed if /profile is used)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Service Routes
    Route::apiResource('services', ServiceController::class);

    // Order Routes
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::put('/orders/{order}/assign-staff', [OrderController::class, 'assignStaff']);
    Route::post('/orders/{id}/evidence', [OrderController::class, 'uploadEvidence']);
    Route::post('/orders/{order}/complete', [OrderController::class, 'completeOrder']);
  
    
    
    // Rating Route
    Route::post('/orders/{order}/ratings', [RatingController::class, 'store']);

    // Subscription Plan Routes
    Route::apiResource('plans', PlanController::class);

    // Vendor routes
    Route::get('/vendor/dashboard-stats', [VendorDashboardController::class, 'index']);
    Route::get('/vendor/history', [VendorDashboardController::class, 'history']);
    Route::get('/vendors', [AdminVendorController::class, 'index']);
    Route::post('/vendors', [AdminVendorController::class, 'store']);
    Route::get('/vendors/{id}', [AdminVendorController::class, 'show']);
    Route::put('/vendors/{id}', [AdminVendorController::class, 'update']);
    // Route::delete('/vendors/{id}', [AdminVendorController::class, 'destroy']);
    Route::delete('/admin/vendors/{vendor}', [VendorController::class, 'destroy']);

    
    // Staff Management Routes
    Route::get('/staffs', [StaffController::class, 'index']);
    Route::post('/staffs', [StaffController::class, 'store']);
    Route::get('/staffs/{id}', [StaffController::class, 'show']);
    Route::put('/staffs/{id}', [StaffController::class, 'update']);
    Route::delete('/staffs/{id}', [StaffController::class, 'destroy']);

    // Client Subscription Routes
    Route::post('/subscriptions', [ClientSubscriptionController::class, 'store']);
    Route::get('/subscriptions', [ClientSubscriptionController::class, 'index']);

    // Payment Routes
    Route::post('/payments/initiate', [PaymentController::class, 'initiatePayment']);
    Route::post('/payments/verify', [PaymentController::class, 'verifyPayment']);
    Route::post('/payments/initiate-subscription', [PaymentController::class, 'initiateSubscriptionPayment']);
    Route::get('/vendor/transactions', [TransactionController::class, 'indexVendor']);

    // Admin Routes
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        
        // Staff & Vendor Admin Management
        Route::get('/staff', [StaffAndVendorAdminController::class, 'getAllStaff']);
        Route::post('/staff', [StaffAndVendorAdminController::class, 'createStaff']);
        Route::get('/staff/{id}', [StaffAndVendorAdminController::class, 'getStaff']);
        Route::put('/staff/{id}', [StaffAndVendorAdminController::class, 'updateStaff']);
        Route::get('/vendors', [StaffAndVendorAdminController::class, 'getAllVendors']);
        Route::post('/vendors', [StaffAndVendorAdminController::class, 'createVendor']);
        Route::get('/vendors/{id}', [StaffAndVendorAdminController::class, 'getVendor']);
        Route::put('/vendors/{id}', [StaffAndVendorAdminController::class, 'updateVendor']);
        Route::delete('/vendors/{id}', [StaffAndVendorAdminController::class, 'deleteVendor']);

        // Banner Routes
        Route::get('/banners', [BannerController::class, 'index']);
        Route::post('/banners', [BannerController::class, 'store']);
        Route::delete('/banners/{id}', [BannerController::class, 'destroy']);
        Route::put('/banners/{id}/toggle', [BannerController::class, 'toggleStatus']);

        // New: View all transactions
        Route::get('/transactions', [TransactionController::class, 'indexAdmin']);
    });
});