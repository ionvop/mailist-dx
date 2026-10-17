<?php

namespace App\Notifications;

use App\Models\UserComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notify a user that another user has commented on their profile.
 */
class ProfileCommentCreated extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly UserComment $comment,
    ) {}

    /**
     * Get the channels the notification should be delivered on.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * The notification's database representation.
     *
     * @return array<string, string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New profile comment',
            'body' => "{$this->comment->user->username} commented on your profile: {$this->comment->body}",
            'url' => route('users.show', $this->comment->profileUser),
        ];
    }
}
