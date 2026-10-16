<?php

namespace App\Enums;

enum UserRole: string
{
    case Member = 'member';
    case Approver = 'approver';

    /**
     * Human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::Approver => 'Approver',
        };
    }
}
