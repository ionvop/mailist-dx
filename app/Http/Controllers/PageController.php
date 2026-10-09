<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function search(): View
    {
        return view('pages.search');
    }

    public function feed(): View
    {
        return view('pages.feed');
    }

    public function list(Request $request): View
    {
        $charts = $request->user()
            ?->charts()
            ->withCount('difficulties')
            ->latest()
            ->get() ?? collect();

        return view('pages.list', [
            'charts' => $charts,
        ]);
    }

    public function profile(): View
    {
        return view('pages.profile');
    }

    public function settings(Request $request): View
    {
        $user = $request->user();

        return view('pages.settings', [
            'canChangeUsername' => $user?->canChangeUsername() ?? false,
            'usernameAvailableAt' => $user?->usernameChangeAvailableAt()->toIso8601String(),
        ]);
    }

    public function notifications(): View
    {
        return view('pages.notifications');
    }
}
