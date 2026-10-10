<?php

use App\Http\Controllers\Auth\EmailLoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Charts\ChartCollaboratorController;
use App\Http\Controllers\Charts\ChartCommentController;
use App\Http\Controllers\Charts\ChartController;
use App\Http\Controllers\Charts\ChartFileController;
use App\Http\Controllers\Charts\ChartSettingsController;
use App\Http\Controllers\Charts\ChartUploadController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Settings\ProfileController;
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

    Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');

    Route::get('/charts/upload', [ChartUploadController::class, 'create'])->name('charts.upload');
    Route::post('/charts', [ChartUploadController::class, 'store'])->name('charts.store');
    Route::get('/charts/{chart}', [ChartController::class, 'show'])->name('charts.show');
    Route::get('/charts/{chart}/files/{type}', [ChartFileController::class, 'show'])->name('charts.files');

    Route::get('/charts/{chart}/settings', [ChartSettingsController::class, 'edit'])->name('charts.settings');
    Route::patch('/charts/{chart}/settings', [ChartSettingsController::class, 'update'])->name('charts.settings.update');
    Route::patch('/charts/{chart}/settings/assets', [ChartSettingsController::class, 'updateAssets'])->name('charts.settings.assets');
    Route::patch('/charts/{chart}/settings/visibility', [ChartSettingsController::class, 'updateVisibility'])->name('charts.settings.visibility');
    Route::post('/charts/{chart}/settings/collaborators', [ChartCollaboratorController::class, 'store'])->name('charts.settings.collaborators.store');
    Route::delete('/charts/{chart}/settings/collaborators/{user}', [ChartCollaboratorController::class, 'destroy'])->name('charts.settings.collaborators.destroy');
    Route::delete('/charts/{chart}', [ChartSettingsController::class, 'destroy'])->name('charts.destroy');

    Route::get('/charts/{chart}/comments', [ChartCommentController::class, 'index'])->name('charts.comments.index');
    Route::post('/charts/{chart}/comments', [ChartCommentController::class, 'store'])->name('charts.comments.store');
    Route::delete('/charts/{chart}/comments/{comment}', [ChartCommentController::class, 'destroy'])->name('charts.comments.destroy');
});
