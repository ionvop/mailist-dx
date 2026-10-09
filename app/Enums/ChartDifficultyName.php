<?php

namespace App\Enums;

enum ChartDifficultyName: string
{
    case Easy = 'easy';
    case Basic = 'basic';
    case Advanced = 'advanced';
    case Expert = 'expert';
    case Master = 'master';
    case Remaster = 'remaster';
    case Utage = 'utage';
    case Custom = 'custom';

    /**
     * Human-readable label for the difficulty.
     */
    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Easy',
            self::Basic => 'Basic',
            self::Advanced => 'Advanced',
            self::Expert => 'Expert',
            self::Master => 'Master',
            self::Remaster => 'Re:Master',
            self::Utage => 'Utage',
            self::Custom => 'Custom',
        };
    }

    /**
     * Map a maidata chart index (1-9) to its default difficulty.
     */
    public static function fromChartIndex(string $index): self
    {
        return match ($index) {
            '1' => self::Easy,
            '2' => self::Basic,
            '3' => self::Advanced,
            '4' => self::Expert,
            '5' => self::Master,
            '6' => self::Remaster,
            '7' => self::Utage,
            default => self::Custom,
        };
    }
}
