<?php

namespace App\Http\Controllers;

use App\Enums\ChartStatus;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * The number of charts shown per page on the list page.
     */
    private const CHARTS_PER_PAGE = 12;

    /**
     * The number of users shown per page on the follower and following pages.
     */
    private const USERS_PER_PAGE = 20;

    /**
     * The number of charts previewed in each profile section.
     */
    private const PROFILE_PREVIEW_COUNT = 3;

    /**
     * The number of charts previewed in each home page section.
     */
    private const HOME_PREVIEW_COUNT = 3;

    public function home(): View
    {
        return view('pages.home', [
            'trendingApproved' => $this->homeCharts(approved: true, sort: 'trending'),
            'recentApproved' => $this->homeCharts(approved: true, sort: 'recent'),
            'trendingNonApproved' => $this->homeCharts(approved: false, sort: 'trending'),
            'recentNonApproved' => $this->homeCharts(approved: false, sort: 'recent'),
        ]);
    }

    /**
     * The charts shown in a single home page section. Approved charts are
     * limited to the approved status, while non-approved charts cover every
     * other status. Private charts are never shown.
     *
     * @return Collection<int, Chart>
     */
    private function homeCharts(bool $approved, string $sort): Collection
    {
        return Chart::query()
            ->where('is_private', false)
            ->when($approved, fn ($query) => $query->where('status', ChartStatus::Approved))
            ->when(! $approved, fn ($query) => $query->where('status', '!=', ChartStatus::Approved))
            ->with(['difficulties', 'uploader'])
            ->withCount('likes')
            ->when($sort === 'trending', fn ($query) => $query
                ->withCount(['likes as weekly_likes_count' => fn ($query) => $query
                    ->where('chart_likes.created_at', '>=', now()->subWeek())])
                ->orderByDesc('weekly_likes_count'))
            ->latest()
            ->limit(self::HOME_PREVIEW_COUNT)
            ->get();
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

    /**
     * The charts the user has liked and charts uploaded by users they follow,
     * deduplicated and sorted by the most recent revision across any of the
     * chart's difficulties. Private charts are never shown.
     */
    public function feed(Request $request): View
    {
        $user = $request->user();

        $charts = Chart::query()
            ->where('is_private', false)
            ->when($user, fn ($query) => $query->where(fn ($query) => $query
                ->whereIn('charts.id', fn ($sub) => $sub
                    ->select('chart_id')
                    ->from('chart_likes')
                    ->where('user_id', $user->id))
                ->orWhereIn('charts.uploader_id', fn ($sub) => $sub
                    ->select('followed_id')
                    ->from('follows')
                    ->where('follower_id', $user->id))))
            ->when(! $user, fn ($query) => $query->whereRaw('1 = 0'))
            ->with(['difficulties.latestRevision.user', 'uploader'])
            ->withCount('likes')
            ->withMax('revisions', 'created_at')
            ->orderByDesc('revisions_max_created_at')
            ->paginate(self::CHARTS_PER_PAGE)
            ->withQueryString();

        return view('pages.feed', [
            'charts' => $charts,
        ]);
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
        return view('pages.profile', $this->profileData($request->user()));
    }

    public function user(User $user): View
    {
        return view('pages.profile', $this->profileData($user));
    }

    /**
     * The charts uploaded by the given user.
     */
    public function charts(User $user): View
    {
        return $this->chartList($user, 'uploaded');
    }

    /**
     * The charts the given user has liked.
     */
    public function likedCharts(User $user): View
    {
        return $this->chartList($user, 'liked');
    }

    /**
     * Render a paginated list of a user's uploaded or liked charts. Private
     * charts are never shown here, even to the owner.
     */
    private function chartList(User $user, string $mode): View
    {
        $charts = $mode === 'liked'
            ? $user->likedCharts()
                ->where('is_private', false)
                ->with(['difficulties', 'uploader'])
                ->withCount('likes')
                ->orderByDesc('chart_likes.created_at')
                ->paginate(self::CHARTS_PER_PAGE)
                ->withQueryString()
            : $user->charts()
                ->where('is_private', false)
                ->with(['difficulties', 'uploader'])
                ->withCount('likes')
                ->latest()
                ->paginate(self::CHARTS_PER_PAGE)
                ->withQueryString();

        return view('pages.user-charts', [
            'profileUser' => $user,
            'charts' => $charts,
            'mode' => $mode,
        ]);
    }

    /**
     * The data shared by the own-profile and public-profile pages.
     *
     * @return array<string, mixed>
     */
    private function profileData(?User $profileUser): array
    {
        if ($profileUser === null) {
            return [
                'profileUser' => null,
                'uploadedCharts' => collect(),
                'uploadedCount' => 0,
                'likedCharts' => collect(),
                'likedCount' => 0,
                'profileComments' => collect(),
                'commentsCount' => 0,
            ];
        }

        $profileUser->loadCount(['followers', 'following']);

        $uploaded = $profileUser->charts()
            ->where('is_private', false)
            ->with(['difficulties', 'uploader'])
            ->withCount('likes')
            ->latest();

        $liked = $profileUser->likedCharts()
            ->where('is_private', false)
            ->with(['difficulties', 'uploader'])
            ->withCount('likes')
            ->orderByDesc('chart_likes.created_at');

        return [
            'profileUser' => $profileUser,
            'uploadedCharts' => $uploaded->limit(self::PROFILE_PREVIEW_COUNT)->get(),
            'uploadedCount' => $profileUser->charts()->where('is_private', false)->count(),
            'likedCharts' => $liked->limit(self::PROFILE_PREVIEW_COUNT)->get(),
            'likedCount' => $profileUser->likedCharts()->where('is_private', false)->count(),
            'profileComments' => $profileUser->receivedProfileComments()->with('user')->get(),
            'commentsCount' => $profileUser->receivedProfileComments()->count(),
        ];
    }

    /**
     * The users following the given user.
     */
    public function followers(Request $request, User $user): View
    {
        return $this->followList($request, $user, 'followers');
    }

    /**
     * The users the given user follows.
     */
    public function following(Request $request, User $user): View
    {
        return $this->followList($request, $user, 'following');
    }

    /**
     * Render a paginated list of a user's followers or followed users.
     */
    private function followList(Request $request, User $user, string $mode): View
    {
        $currentUser = $request->user();

        $users = $user->{$mode}()
            ->withCount(['followers', 'following'])
            ->withExists(['followers as is_followed_by_current_user' => fn ($query) => $query
                ->whereKey($currentUser?->id ?? 0)])
            ->orderBy('username')
            ->paginate(self::USERS_PER_PAGE)
            ->withQueryString();

        return view('pages.follows', [
            'profileUser' => $user,
            'users' => $users,
            'mode' => $mode,
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
