<?php

namespace App\Notifications;

use App\Models\ChartDifficultyImprovement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notify a chart's uploader that another user has suggested an improvement on
 * one of its difficulties.
 */
class ImprovementSuggested extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly ChartDifficultyImprovement $improvement,
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
        $difficulty = $this->improvement->difficulty;

        return [
            'title' => 'Improvement suggested',
            'body' => "{$this->improvement->user->username} suggested an improvement on {$difficulty->displayName()} of \"{$difficulty->chart->title}\": {$this->improvement->body}",
            'url' => route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'improvements']),
        ];
    }
}
