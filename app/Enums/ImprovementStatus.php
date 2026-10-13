<?php

namespace App\Enums;

enum ImprovementStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Resolved => 'Resolved',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Tailwind border/text/background classes used to tint a status badge.
     */
    public function colorClasses(): string
    {
        return match ($this) {
            self::Open => 'border-sky-500/40 bg-sky-500/10 text-sky-300',
            self::Resolved => 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300',
            self::Rejected => 'border-rose-500/40 bg-rose-500/10 text-rose-300',
        };
    }
}
