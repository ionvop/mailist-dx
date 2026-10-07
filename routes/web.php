<?php

use App\Http\Controllers\DevAuthController;
use App\Http\Controllers\PageController;
use App\Support\DevAuth;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/search', [PageController::class, 'search'])->name('search');
Route::get('/feed', [PageController::class, 'feed'])->name('feed');
Route::get('/list', [PageController::class, 'list'])->name('list');
Route::get('/profile', [PageController::class, 'profile'])->name('profile');
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/settings', [PageController::class, 'settings'])->name('settings');
Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications');

/*
 * Development-only auth toggle so the guest and signed-in chrome can both be
 * previewed. Registered only outside production.
 */
if (DevAuth::enabled()) {
    Route::post('/dev/auth/toggle', [DevAuthController::class, 'toggle'])->name('dev.auth.toggle');
}
