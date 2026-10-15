<?php

use App\Http\Controllers\Auth\EmailLoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Charts\ChartCollaboratorController;
use App\Http\Controllers\Charts\ChartCommentController;
use App\Http\Controllers\Charts\ChartController;
use App\Http\Controllers\Charts\ChartDifficultyCommentController;
use App\Http\Controllers\Charts\ChartDifficultyController;
use App\Http\Controllers\Charts\ChartDifficultyImprovementController;
use App\Http\Controllers\Charts\ChartDifficultySettingsController;
use App\Http\Controllers\Charts\ChartFileController;
use App\Http\Controllers\Charts\ChartLikeController;
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
Route::get('/users/{user:username}', [PageController::class, 'user'])->name('users.show');
Route::get('/settings', [PageController::class, 'settings'])->name('settings');
Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications');

/*
 * Public chart browsing. These read-only routes are reachable by guests; the
 * controllers still hide private charts from anyone who cannot edit them.
 */
Route::get('/charts/{chart}', [ChartController::class, 'show'])
    ->whereNumber('chart')->name('charts.show');
Route::get('/charts/{chart}/files/{type}', [ChartFileController::class, 'show'])
    ->whereNumber('chart')->name('charts.files');
Route::get('/charts/{chart}/difficulties/{difficulty}/{tab?}', [ChartDifficultyController::class, 'show'])
    ->whereNumber('chart')->name('charts.difficulties.show');
Route::get('/charts/{chart}/difficulties/{difficulty}/revisions/{revision}/diff', [ChartDifficultyController::class, 'diff'])
    ->whereNumber('chart')->name('charts.difficulties.revisions.diff');
Route::get('/charts/{chart}/comments', [ChartCommentController::class, 'index'])
    ->whereNumber('chart')->name('charts.comments.index');

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

    Route::patch('/charts/{chart}/difficulties/{difficulty}', [ChartDifficultyController::class, 'update'])
        ->name('charts.difficulties.update');
    Route::post('/charts/{chart}/difficulties/{difficulty}/revisions/{revision}/restore', [ChartDifficultyController::class, 'restore'])
        ->name('charts.difficulties.revisions.restore');
    Route::patch('/charts/{chart}/difficulties/{difficulty}/revisions/{revision}/note', [ChartDifficultyController::class, 'updateNote'])
        ->name('charts.difficulties.revisions.note.update');

    Route::post('/charts/{chart}/difficulties/{difficulty}/improvements', [ChartDifficultyImprovementController::class, 'store'])
        ->name('charts.difficulties.improvements.store');
    Route::patch('/charts/{chart}/difficulties/{difficulty}/improvements/{improvement}', [ChartDifficultyImprovementController::class, 'update'])
        ->name('charts.difficulties.improvements.update');
    Route::patch('/charts/{chart}/difficulties/{difficulty}/improvements/{improvement}/revoke', [ChartDifficultyImprovementController::class, 'revoke'])
        ->name('charts.difficulties.improvements.revoke');
    Route::delete('/charts/{chart}/difficulties/{difficulty}/improvements/{improvement}', [ChartDifficultyImprovementController::class, 'destroy'])
        ->name('charts.difficulties.improvements.destroy');

    Route::post('/charts/{chart}/difficulties/{difficulty}/comments', [ChartDifficultyCommentController::class, 'store'])
        ->name('charts.difficulties.comments.store');
    Route::delete('/charts/{chart}/difficulties/{difficulty}/comments/{comment}', [ChartDifficultyCommentController::class, 'destroy'])
        ->name('charts.difficulties.comments.destroy');

    Route::patch('/charts/{chart}/difficulties/{difficulty}/settings', [ChartDifficultySettingsController::class, 'update'])
        ->name('charts.difficulties.settings.update');

    Route::get('/charts/{chart}/settings', [ChartSettingsController::class, 'edit'])->name('charts.settings');
    Route::patch('/charts/{chart}/settings', [ChartSettingsController::class, 'update'])->name('charts.settings.update');
    Route::patch('/charts/{chart}/settings/assets', [ChartSettingsController::class, 'updateAssets'])->name('charts.settings.assets');
    Route::patch('/charts/{chart}/settings/visibility', [ChartSettingsController::class, 'updateVisibility'])->name('charts.settings.visibility');
    Route::post('/charts/{chart}/settings/collaborators', [ChartCollaboratorController::class, 'store'])->name('charts.settings.collaborators.store');
    Route::delete('/charts/{chart}/settings/collaborators/{user}', [ChartCollaboratorController::class, 'destroy'])->name('charts.settings.collaborators.destroy');
    Route::delete('/charts/{chart}', [ChartSettingsController::class, 'destroy'])->name('charts.destroy');

    Route::post('/charts/{chart}/comments', [ChartCommentController::class, 'store'])->name('charts.comments.store');
    Route::delete('/charts/{chart}/comments/{comment}', [ChartCommentController::class, 'destroy'])->name('charts.comments.destroy');

    Route::post('/charts/{chart}/likes', [ChartLikeController::class, 'store'])->name('charts.likes.store');
    Route::delete('/charts/{chart}/likes', [ChartLikeController::class, 'destroy'])->name('charts.likes.destroy');
});
