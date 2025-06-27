<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\Auth\ResendVerificationCodeController;
use App\Http\Controllers\Api\Auth\AdminLoginController;
use App\Http\Controllers\Api\Auth\AdminRegisterController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Api\Chat\ChatController;

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

// Auth routes
Route::post('/login', LoginController::class);
Route::post('/register', RegisterController::class);
Route::post('/verify-email', VerifyEmailController::class);
Route::post('/resend-verification-code', ResendVerificationCodeController::class);

// Chat routes (para usuários e admins)
Route::middleware('auth:sanctum')->prefix('chat')->group(function () {
    Route::post('/send', [ChatController::class, 'sendMessage']);
    Route::get('/conversation', [ChatController::class, 'getConversation']);
    Route::get('/conversations', [ChatController::class, 'getConversations']);
});

// Admin Auth routes
Route::prefix('admin')->group(function () {
    Route::post('/login', AdminLoginController::class);
    Route::post('/register', AdminRegisterController::class);
    
    // Protected admin routes
    Route::middleware(['auth:sanctum', 'admin.auth'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        
        // Admin chat routes
        Route::prefix('chat')->group(function () {
            Route::get('/conversations', [AdminChatController::class, 'getConversations']);
            Route::get('/conversation', [AdminChatController::class, 'getConversationWithUser']);
            Route::post('/send', [AdminChatController::class, 'sendMessageToUser']);
        });
    });
}); 
