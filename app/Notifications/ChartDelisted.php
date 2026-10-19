<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Chart;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notify a chart's uploader that a moderator has delisted or restored their
 * chart.
 */
class ChartDelisted extends Notification
{
    use SerializesModels;

    /**
     * @param  string  $title  Short heading shown in the notification list.
     * @param  string  $body  One-sentence description of the change.
     */
    public function __construct(
        private readonly Chart $chart,
        private readonly string $title,
        private readonly string $body,
    ) {}

    /**
     * Get the channels the notification should be delivered on.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsNotification(NotificationType::ChartStatusChanged->value)
            ? ['database']
            : [];
    }

    /**
     * The notification's database representation.
     *
     * @return array<string, string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => route('charts.show', $this->chart),
        ];
    }
}
