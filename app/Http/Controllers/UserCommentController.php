<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserComment;
use App\Notifications\ContentRemoved;
use App\Notifications\ProfileCommentCreated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserCommentController extends Controller
{
    /**
     * Post a comment on a user's profile. Any authenticated user may comment,
     * including on their own profile.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], [
            'body.required' => 'Please write a comment before posting.',
            'body.max' => 'Your comment may not be longer than 5000 characters.',
        ]);

        $comment = $user->receivedProfileComments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        if (! $user->is($request->user())) {
            $user->notify(new ProfileCommentCreated($comment));
        }

        return back()->with('status', 'Your comment has been posted.');
    }

    /**
     * Delete a comment. The author may delete their own comment, and
     * moderators may delete any comment.
     */
    public function destroy(Request $request, User $user, UserComment $comment): RedirectResponse
    {
        if ($comment->profile_user_id !== $user->id) {
            throw new NotFoundHttpException;
        }

        $isModeration = $comment->user_id !== $request->user()->id;

        if ($isModeration) {
            abort_unless($request->user()->isModerator(), 403);
        }

        $comment->delete();

        if ($isModeration) {
            $comment->user->notify(new ContentRemoved(
                'Comment removed',
                "Your comment on {$user->username}'s profile was removed by a moderator.",
                route('users.show', $user),
            ));

            return back()->with('status', 'The comment has been removed.');
        }

        return back()->with('status', 'Your comment has been deleted.');
    }
}
