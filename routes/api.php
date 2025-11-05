<?php

use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CreditController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProfessionalController;
use App\Http\Controllers\Api\V1\ServiceRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API Version 1
Route::prefix('v1')->group(function () {

    // Public routes
    Route::get('/health', [HealthController::class, 'health']);
    Route::get('/metrics', [HealthController::class, 'metrics']);

    // Authentication routes
    Route::prefix('auth')->group(function () {
        // Public auth routes with rate limiting and IP blocking
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware(['throttle:3,1', 'block.suspicious']);
        
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware(['throttle:5,1', 'block.suspicious']);

        // Password recovery (public)
        Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])
            ->middleware(['throttle:3,1']);
        
        Route::get('/password/reset', [AuthController::class, 'validateResetToken'])
            ->middleware(['throttle:3,1']);
        
        Route::post('/password/reset', [AuthController::class, 'resetPassword'])
            ->middleware(['throttle:3,1']);

        // Email verification (public)
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->name('verification.verify');

        // Protected auth routes
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);
        });
    });

    // Protected routes
    Route::middleware('auth:api')->group(function () {

        // Credit routes
        Route::prefix('credits')->group(function () {
            Route::get('/balance', [CreditController::class, 'balance']);
            Route::get('/transactions', [CreditController::class, 'transactions']);
            Route::post('/deduct', [CreditController::class, 'deduct']);
            Route::post('/transfer', [CreditController::class, 'transfer']);
            Route::post('/add', [CreditController::class, 'add']); // Policy handles authorization
        });

        // Service Request routes
        Route::prefix('service-requests')->group(function () {
            Route::get('/', [ServiceRequestController::class, 'index']);
            Route::post('/', [ServiceRequestController::class, 'store']);
            Route::get('/compatible', [ServiceRequestController::class, 'compatible']);
            Route::get('/{id}', [ServiceRequestController::class, 'show']);
            Route::post('/{id}/respond', [ServiceRequestController::class, 'respond']);
            Route::post('/{id}/cancel', [ServiceRequestController::class, 'cancel']);
            Route::post('/{id}/complete', [ServiceRequestController::class, 'complete']);
        });

        // Professional routes
        Route::prefix('professionals')->group(function () {
            Route::get('/', [ProfessionalController::class, 'index']);
            Route::post('/', [ProfessionalController::class, 'store']);
            Route::get('/me', [ProfessionalController::class, 'me']);
            Route::put('/me', [ProfessionalController::class, 'update']);
            Route::delete('/me', [ProfessionalController::class, 'destroy']);
            Route::get('/{id}', [ProfessionalController::class, 'show']);
        });

        // Admin routes
        Route::prefix('admin')->group(function () {
            // Users
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);

            // Roles
            Route::get('/roles', [RoleController::class, 'index']);
            Route::get('/roles/{role}', [RoleController::class, 'show']);

            // Permissions
            Route::get('/permissions', [PermissionController::class, 'index']);
        });
    });
});
