<?php

use App\Http\Controllers\Auth\LoginWebController;
use App\Http\Controllers\Auth\RegisterWebController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\VerifyEmailWebController;
use App\Http\Controllers\Auth\ResendVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('signin');
    })->name('login');

    Route::post('/login', LoginWebController::class);
});

Route::get('/register', function () {
    return view('signup');
})->name('register');

Route::post('/register', RegisterWebController::class);

// Email verification route - public access (hash is the security)
Route::get('/email/verify/{id}/{hash}', VerifyEmailWebController::class)->name('verification.verify');

Route::middleware('auth')->group(function () {
    // Trang xác minh email
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verify.email.notice');

    Route::post('/email/verification-notification', ResendVerificationController::class)->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', LogoutController::class)->name('logout');
});
