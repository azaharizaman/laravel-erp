<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nexus\Procurement\Http\Controllers\VendorPortalAuthController;
use Nexus\Procurement\Http\Controllers\VendorPortalController;

/*
|--------------------------------------------------------------------------
| Vendor Portal API Routes
|--------------------------------------------------------------------------
|
| Routes for the vendor portal functionality.
| These routes are prefixed with 'api/vendor-portal' and use vendor authentication.
|
*/

Route::middleware(['auth:sanctum'])->prefix('vendor-portal')->name('vendor-portal.')->group(function () {

    // Authentication routes (these use vendor guard)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('login', [VendorPortalAuthController::class, 'login'])->name('login')->withoutMiddleware('auth:sanctum');
        Route::post('logout', [VendorPortalAuthController::class, 'logout'])->name('logout');
        Route::get('profile', [VendorPortalAuthController::class, 'profile'])->name('profile');
        Route::post('request-password-reset', [VendorPortalAuthController::class, 'requestPasswordReset'])->name('request-password-reset')->withoutMiddleware('auth:sanctum');
        Route::post('reset-password', [VendorPortalAuthController::class, 'resetPassword'])->name('reset-password')->withoutMiddleware('auth:sanctum');
    });

    // Vendor management (admin only - would use different middleware)
    Route::post('create-user', [VendorPortalAuthController::class, 'createVendorUser'])->name('create-user');

    // Protected vendor portal routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Dashboard
        Route::get('dashboard', [VendorPortalController::class, 'dashboard'])->name('dashboard');

        // Purchase Orders
        Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
            Route::get('/', [VendorPortalController::class, 'purchaseOrders'])->name('index');
            Route::get('/{purchaseOrder}', [VendorPortalController::class, 'purchaseOrder'])->name('show');
        });

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [VendorPortalController::class, 'invoices'])->name('index');
            Route::post('/', [VendorPortalController::class, 'submitInvoice'])->name('store');
        });

        // Payment Status
        Route::get('payment-status', [VendorPortalController::class, 'paymentStatus'])->name('payment-status');

        // Profile Management
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::put('/', [VendorPortalController::class, 'updateProfile'])->name('update');
            Route::put('password', [VendorPortalController::class, 'changePassword'])->name('change-password');
        });
    });
});