<?php

use App\Http\Controllers\Api\AdminDashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientDashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MailSettingController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\SuppliersController;
use App\Http\Controllers\Api\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('role:admin')->group(
        function () {
            Route::post('/logout', [AuthController::class, 'logout']);

            Route::apiResource('clients', ClientController::class);
            Route::apiResource('categories', CategoryController::class);
            Route::apiResource('products', ProductController::class);


            Route::get('/invoices', [InvoiceController::class, 'index']);
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
            Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
            Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);

            Route::get('/clients/{client}/dashboard', [ClientDashboardController::class, 'show']);

            Route::get('/mail-settings', [MailSettingController::class, 'show']);
            Route::post('/mail-settings', [MailSettingController::class, 'store']);

            Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
            Route::get('/invoices/{invoice}/pdf/download', [InvoiceController::class, 'pdfDownload']);
            Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'sendEmail']);
            Route::post('/mail-settings/test', [MailSettingController::class, 'test']);

            Route::get('/settings', [SiteSettingController::class, 'show']);
            Route::put('/settings', [SiteSettingController::class, 'update']);
            Route::post('/settings/logo', [SiteSettingController::class, 'uploadLogo']);

            Route::get('/dashboard/admin', [AdminDashboardController::class, 'index']);



            Route::get('/users', [UserController::class, 'index']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);


            Route::apiResource('suppliers', SuppliersController::class);
        }

    );

    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('role:seller,admin');
});
