<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientDashboardController;
use App\Http\Controllers\Api\InvoiceAttachmentController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MailSettingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\SuppliersController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // logout (خليه لأي user مسجل دخول)
    Route::post('/logout', [AuthController::class, 'logout']);

    /**
     * Invoices (seller + admin)
     */
    Route::middleware('role:seller,admin')->group(function () {
        // CRUD
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);

        // PDF + Email
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
        Route::get('/invoices/{invoice}/pdf/download', [InvoiceController::class, 'pdfDownload']);
        Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'sendEmail']);

      
        Route::post('/invoices/{invoice}/attachments', [InvoiceAttachmentController::class, 'store']);
        Route::get('/invoices/{invoice}/attachments', [InvoiceAttachmentController::class, 'index']);
        Route::delete('/attachments/{attachment}', [InvoiceAttachmentController::class, 'destroy']);
    });


    Route::middleware('role:admin')->group(function () {

        Route::apiResource('clients', ClientController::class);
        Route::get('/clients/{client}/dashboard', [ClientDashboardController::class, 'show']);

        Route::apiResource('categories', CategoryController::class);

        // ✅ Products (مع فلتر low_stock وغيره داخل controller)
        Route::apiResource('products', ProductController::class);

        Route::apiResource('suppliers', SuppliersController::class);

        // Mail settings
        Route::get('/mail-settings', [MailSettingController::class, 'show']);
        Route::post('/mail-settings', [MailSettingController::class, 'store']);
        Route::post('/mail-settings/test', [MailSettingController::class, 'test']);

        // Site settings
        Route::get('/settings', [SiteSettingController::class, 'show']);
        Route::put('/settings', [SiteSettingController::class, 'update']);
        Route::post('/settings/logo', [SiteSettingController::class, 'uploadLogo']);

        // Admin dashboard
        Route::get('/dashboard/admin', [AdminDashboardController::class, 'index']);

        // Users
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
