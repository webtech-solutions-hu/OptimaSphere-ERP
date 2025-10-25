<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication routes (public)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Public API routes (read-only)
Route::prefix('v1')->group(function () {
    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    // Units
    Route::get('/units', [UnitController::class, 'index']);
    Route::get('/units/{unit}', [UnitController::class, 'show']);
});

// Protected API routes (requires authentication)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/tokens', [AuthController::class, 'tokens']);
        Route::delete('/tokens/{tokenId}', [AuthController::class, 'revokeToken']);
    });

    // Products - Full CRUD
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/bulk', [ProductController::class, 'bulkStore']);
    Route::post('/products/import', [ProductController::class, 'import']);
    Route::get('/products/export', [ProductController::class, 'export']);

    // Categories - Full CRUD
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Units - Full CRUD
    Route::post('/units', [UnitController::class, 'store']);
    Route::put('/units/{unit}', [UnitController::class, 'update']);
    Route::delete('/units/{unit}', [UnitController::class, 'destroy']);

    // Customers - Full CRUD
    Route::apiResource('customers', CustomerController::class);
    Route::post('/customers/import', [CustomerController::class, 'import']);
    Route::get('/customers/export', [CustomerController::class, 'export']);

    // Suppliers - Full CRUD
    Route::apiResource('suppliers', SupplierController::class);
    Route::post('/suppliers/{supplier}/approve', [SupplierController::class, 'approve']);
    Route::post('/suppliers/import', [SupplierController::class, 'import']);
    Route::get('/suppliers/export', [SupplierController::class, 'export']);
});
