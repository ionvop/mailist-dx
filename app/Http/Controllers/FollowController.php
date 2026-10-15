<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FollowController extends Controller
{
    /**
     * Follow another user. A user cannot follow themselves.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        $this->ensureNotSelf($request, $user);

        $request->user()->following()->syncWithoutDetaching([$user->id]);

        return back()->with('status', "You are now following {$user->username}.");
    }

    /**
     * Stop following another user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureNotSelf($request, $user);

        $request->user()->following()->detach($user->id);

        return back()->with('status', "You are no longer following {$user->username}.");
    }

    /**
     * Abort with a 404 when a user tries to follow themselves.
     */
    private function ensureNotSelf(Request $request, User $user): void
    {
        if ($request->user()->is($user)) {
            throw new NotFoundHttpException;
        }
    }
}
