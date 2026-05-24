<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordSetupController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
| Login, Register, Google OAuth, Email Verification, Password Reset
*/

Route::middleware('guest')->group(function () {
    Route::get('/login',  [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('auth.login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('auth.login.post');

    Route::get('/register',  [\App\Http\Controllers\Auth\RegisterController::class, 'showRegisterForm'])->name('auth.register');
    Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('auth.register.post');

    Route::get('/verify-email',          [EmailVerificationController::class, 'show'])->name('auth.verify-email');
    Route::post('/verify-email',         [EmailVerificationController::class, 'verify'])->name('auth.verify-email.post');
    Route::post('/verify-email/resend',  [EmailVerificationController::class, 'resend'])->name('auth.verify-email.resend');

    Route::get('/auth/google/redirect',  [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback',  [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    Route::get('/forgot-password',       [ForgotPasswordController::class, 'show'])->name('auth.forgot-password');
    Route::post('/forgot-password',      [ForgotPasswordController::class, 'send'])->name('auth.forgot-password.post');

    Route::get('/reset-password',        [ResetPasswordController::class, 'show'])->name('auth.reset-password');
    Route::post('/reset-password/verify',[ResetPasswordController::class, 'verifyCode'])->name('auth.reset-password.verify');
    Route::post('/reset-password',       [ResetPasswordController::class, 'update'])->name('auth.reset-password.post');
});

Route::middleware('auth')->group(function () {
    Route::get('/set-password',  [PasswordSetupController::class, 'show'])->name('auth.password.setup');
    Route::post('/set-password', [PasswordSetupController::class, 'update'])->name('auth.password.setup.post');

    Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('auth.logout');
});
