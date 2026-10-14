<?php

namespace App\Http\Controllers;

use App\Models\Chart;
use App\Models\User;
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
        $user = $request->user();

        $charts = $user
            ? Chart::query()
                ->with(['difficulties', 'uploader'])
                ->where(fn ($query) => $query
                    ->where('uploader_id', $user->id)
                    ->orWhereHas('collaborators', fn ($query) => $query->whereKey($user->id)))
                ->latest()
                ->get()
            : collect();

        return view('pages.list', [
            'charts' => $charts,
        ]);
    }

    public function profile(Request $request): View
    {
        return view('pages.profile', [
            'profileUser' => $request->user(),
        ]);
    }

    public function user(User $user): View
    {
        return view('pages.profile', [
            'profileUser' => $user,
        ]);
    }

    public function settings(Request $request): View
    {
        $user = $request->user();

        return view('pages.settings', [
            'canChangeUsername' => $user?->canChangeUsername() ?? false,
            'usernameAvailableAt' => $user?->usernameChangeAvailableAt()->utc()->toIso8601String(),
        ]);
    }

    public function notifications(): View
    {
        return view('pages.notifications');
    }
}
