<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('signin');
    })->name('login');

    // Route::get('/register', function () {
    //     return view('signup');
    // })->name('register');

    // Route::post('/register', [AuthController::class, 'registerWeb']);
    Route::post('/login', [AuthController::class, 'loginWeb']);
});

Route::get('/register', function () {
    return view('signup');
})->name('register');

Route::post('/register', [AuthController::class, 'registerWeb']);

// Email verification route - public access (hash is the security)
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmailWeb'])->name('verification.verify');

Route::middleware('auth')->group(function () {
    // Trang xác minh email
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verify.email.notice');

    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
