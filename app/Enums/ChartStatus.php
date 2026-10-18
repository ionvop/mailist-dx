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

    /**
     * Tailwind border/text/background classes used to tint a status badge.
     */
    public function colorClasses(): string
    {
        return match ($this) {
            self::Wip => 'border-amber-500/40 bg-amber-500/10 text-amber-300',
            self::ForReview => 'border-violet-500/40 bg-violet-500/10 text-violet-300',
            self::Completed => 'border-blue-500/40 bg-blue-500/10 text-blue-300',
            self::Approved => 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300',
        };
    }
}
