<?php

namespace App\Http\Controllers;

use App\Enums\ChartStatus;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * The number of charts shown per page on the list page.
     */
    private const CHARTS_PER_PAGE = 12;

    public function home(): View
    {
        return view('pages.home');
    }

    public function search(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $status = ChartStatus::tryFrom($request->string('status')->toString());

        $sort = $request->string('sort')->toString();
        $sort = in_array($sort, ['recent', 'trending', 'popularity'], true) ? $sort : 'recent';

        $charts = Chart::query()
            ->where('is_private', false)
            ->with(['difficulties', 'uploader'])
            ->withCount('likes')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('artist', 'like', "%{$search}%")
                ->orWhere('note_designer', 'like', "%{$search}%")))
            ->when($sort === 'popularity', fn ($query) => $query
                ->orderByDesc('likes_count'))
            ->when($sort === 'trending', fn ($query) => $query
                ->withCount(['likes as weekly_likes_count' => fn ($query) => $query
                    ->where('chart_likes.created_at', '>=', now()->subWeek())])
                ->orderByDesc('weekly_likes_count'))
            ->latest()
            ->paginate(self::CHARTS_PER_PAGE)
            ->withQueryString();

        return view('pages.search', [
            'charts' => $charts,
            'filters' => [
                'q' => $search,
                'status' => $status?->value,
                'sort' => $sort,
            ],
        ]);
    }

    public function feed(): View
    {
        return view('pages.feed');
    }

    public function list(Request $request): View
    {
        $user = $request->user();

        $ownership = $request->string('ownership')->toString();
        $ownership = in_array($ownership, ['all', 'owned', 'shared'], true) ? $ownership : 'all';

        $status = ChartStatus::tryFrom($request->string('status')->toString());
        $search = trim($request->string('q')->toString());

        $charts = Chart::query()
            ->with(['difficulties', 'uploader'])
            ->withCount('likes')
            ->when(! $user, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($user, function ($query) use ($user, $ownership) {
                if ($ownership === 'owned') {
                    return $query->where('uploader_id', $user->id);
                }

                if ($ownership === 'shared') {
                    return $query->whereHas('collaborators', fn ($query) => $query->whereKey($user->id));
                }

                return $query->where(fn ($query) => $query
                    ->where('uploader_id', $user->id)
                    ->orWhereHas('collaborators', fn ($query) => $query->whereKey($user->id)));
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('artist', 'like', "%{$search}%")))
            ->latest()
            ->paginate(self::CHARTS_PER_PAGE)
            ->withQueryString();

        return view('pages.list', [
            'charts' => $charts,
            'filters' => [
                'ownership' => $ownership,
                'status' => $status?->value,
                'q' => $search,
            ],
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
