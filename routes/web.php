<?php

use App\Http\Controllers\Auth\EmailLoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/search', [PageController::class, 'search'])->name('search');
Route::get('/feed', [PageController::class, 'feed'])->name('feed');
Route::get('/list', [PageController::class, 'list'])->name('list');
Route::get('/profile', [PageController::class, 'profile'])->name('profile');
Route::get('/settings', [PageController::class, 'settings'])->name('settings');
Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications');

/*
 * Passwordless authentication: a one-time email code or Google OAuth.
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [EmailLoginController::class, 'show'])->name('login');

    Route::post('/login/email', [EmailLoginController::class, 'send'])
        ->middleware('throttle:5,1')
        ->name('login.email');

    Route::get('/login/email/verify', [EmailLoginController::class, 'verify'])->name('login.verify');

    Route::post('/login/email/verify', [EmailLoginController::class, 'verifyCode'])
        ->middleware('throttle:5,1')
        ->name('login.verify.submit');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
});
