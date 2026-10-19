<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notify a chart's uploader that another user has commented on their chart.
 */
class ChartCommentCreated extends Notification
{
    use SerializesModels;

    public function __construct(
        private readonly Comment $comment,
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
            'title' => 'New chart comment',
            'body' => "{$this->comment->user->username} commented on \"{$this->comment->chart->title}\": {$this->comment->body}",
            'url' => route('charts.comments.index', $this->comment->chart),
        ];
    }
}
