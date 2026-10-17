<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    /**
     * The number of notifications shown per page.
     */
    private const NOTIFICATIONS_PER_PAGE = 20;

    /**
     * The signed-in user's notifications, newest first.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('pages.notifications', [
            'notifications' => $user->notifications()
                ->latest()
                ->paginate(self::NOTIFICATIONS_PER_PAGE)
                ->withQueryString(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark every unread notification as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }

    /**
     * Mark a single notification as read, then continue to the page it points
     * at. The lookup is scoped to the current user so nobody can mark another
     * account's notifications.
     */
    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        $url = $record->data['url'] ?? null;

        if (is_string($url) && Str::startsWith($url, url('/'))) {
            return redirect()->to($url);
        }

        return redirect()->route('notifications');
    }
}
