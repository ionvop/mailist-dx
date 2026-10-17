<?php

namespace App\Providers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ViewFactory::composer('layouts.app', function (View $view): void {
            $user = Auth::user();

            $view->with('unreadNotificationCount', $user !== null ? $user->unreadNotifications()->count() : 0);
        });
    }
}
