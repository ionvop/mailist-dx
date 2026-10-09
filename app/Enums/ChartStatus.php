<?php

namespace App\Enums;

enum ChartStatus: string
{
    case Wip = 'wip';
    case ForReview = 'for_review';
    case Completed = 'completed';
    case Approved = 'approved';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Wip => 'WIP',
            self::ForReview => 'For Review',
            self::Completed => 'Completed',
            self::Approved => 'Approved',
        };
    }
}
