<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notify a user that a moderator removed one of their contributions, such as
 * a comment or an improvement suggestion.
 */
class ContentRemoved extends Notification
{
    use SerializesModels;

    /**
     * @param  string  $title  Short heading shown in the notification list.
     * @param  string  $body  One-sentence description of the removal.
     * @param  string|null  $url  Optional link to the page the content was on.
     */
    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly ?string $url = null,
    ) {}

    /**
     * Get the channels the notification should be delivered on.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsNotification(NotificationType::ContentRemoved->value)
            ? ['database']
            : [];
    }

    /**
     * The notification's database representation.
     *
     * @return array<string, string|null>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }
}
