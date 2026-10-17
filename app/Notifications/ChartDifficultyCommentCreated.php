<?php

namespace App\Notifications;

use App\Models\ChartDifficultyComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notify a chart's uploader that another user has commented on one of its
 * difficulties.
 */
class ChartDifficultyCommentCreated extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly ChartDifficultyComment $comment,
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
        $difficulty = $this->comment->difficulty;

        return [
            'title' => 'New difficulty comment',
            'body' => "{$this->comment->user->username} commented on {$difficulty->displayName()} of \"{$difficulty->chart->title}\": {$this->comment->body}",
            'url' => route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'comments']),
        ];
    }
}
