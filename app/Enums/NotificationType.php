<?php

namespace App\Enums;

enum NotificationType: string
{
    case ChartComment = 'chart_comment';
    case ChartDifficultyComment = 'chart_difficulty_comment';
    case ChartStatusChanged = 'chart_status_changed';
    case ImprovementSuggested = 'improvement_suggested';
    case ImprovementResolved = 'improvement_resolved';
    case ProfileComment = 'profile_comment';
    case UserFollowed = 'user_followed';

    /**
     * Human-readable label for the notification type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ChartComment => 'Chart comments',
            self::ChartDifficultyComment => 'Difficulty comments',
            self::ChartStatusChanged => 'Chart status changes',
            self::ImprovementSuggested => 'Improvement suggestions',
            self::ImprovementResolved => 'Improvement resolutions',
            self::ProfileComment => 'Profile comments',
            self::UserFollowed => 'New followers',
        };
    }

    /**
     * A short description of what triggers this notification type.
     */
    public function description(): string
    {
        return match ($this) {
            self::ChartComment => 'Someone comments on one of your charts.',
            self::ChartDifficultyComment => 'Someone comments on a difficulty you collaborated on.',
            self::ChartStatusChanged => 'One of your charts is approved or rejected.',
            self::ImprovementSuggested => 'Someone suggests an improvement on your difficulty.',
            self::ImprovementResolved => 'An improvement you suggested is resolved.',
            self::ProfileComment => 'Someone comments on your profile.',
            self::UserFollowed => 'Someone starts following you.',
        };
    }

    /**
     * All notification types as plain string values, for validation.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
