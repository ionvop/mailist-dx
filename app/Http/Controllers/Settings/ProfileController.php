<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's profile (avatar, username, and bio).
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        if (array_key_exists('bio', $validated)) {
            $user->bio = $validated['bio'];
        }

        // Respect the cooldown, and only record a change when the value differs.
        if (
            $user instanceof User
            && is_string($validated['username'] ?? null)
            && $user->canChangeUsername()
            && $validated['username'] !== $user->username
        ) {
            $user->username = $validated['username'];
            $user->username_changed_at = now();
        }

        $user->save();

        return redirect()
            ->route('settings')
            ->with('status', 'Profile updated successfully.');
    }
}
