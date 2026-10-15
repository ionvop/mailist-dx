<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserComment;
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

        $user->receivedProfileComments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return back()->with('status', 'Your comment has been posted.');
    }

    /**
     * Delete a comment. Only the author may delete their own comment.
     */
    public function destroy(Request $request, User $user, UserComment $comment): RedirectResponse
    {
        if ($comment->profile_user_id !== $user->id) {
            throw new NotFoundHttpException;
        }

        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return back()->with('status', 'Your comment has been deleted.');
    }
}
