<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateNotificationPreferencesRequest;
use Illuminate\Http\RedirectResponse;

class NotificationPreferencesController extends Controller
{
    /**
     * Update the authenticated user's notification preferences.
     */
    public function update(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->notification_preferences = $request->preferences();
        $user->save();

        return redirect()
            ->route('settings')
            ->with('status', 'Notification preferences updated successfully.');
    }
}
