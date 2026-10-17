<?php

namespace App\Notifications;

use App\Models\ChartDifficultyImprovement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notify an improvement's author that their suggestion has been resolved or
 * rejected.
 */
class ImprovementResolved extends Notification implements ShouldQueue
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
        $status = $this->improvement->status->label();
        $note = $this->improvement->resolution_note;

        $body = "Your suggestion on {$difficulty->displayName()} of \"{$difficulty->chart->title}\" was {$status}.";

        if ($note !== null && $note !== '') {
            $body .= " Note: {$note}";
        }

        return [
            'title' => "Improvement {$status}",
            'body' => $body,
            'url' => route('charts.difficulties.show', [$difficulty->chart, $difficulty, 'improvements']),
        ];
    }
}
