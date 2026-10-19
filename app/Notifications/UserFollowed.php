<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notify a user that another user has started following them.
 */
class UserFollowed extends Notification
{
    use SerializesModels;

    /**
     * @param  string  $actorUsername  Username of the follower, captured at send time.
     */
    public function __construct(
        private readonly string $actorUsername,
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
            'title' => 'New follower',
            'body' => "{$this->actorUsername} started following you.",
            'url' => route('users.show', $this->actorUsername),
        ];
    }
}
