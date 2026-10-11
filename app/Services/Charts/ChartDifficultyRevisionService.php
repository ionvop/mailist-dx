<?php

namespace App\Services\Charts;

use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyRevision;

/**
 * Records immutable snapshots of a difficulty's simai code so the History tab
 * can show every saved change and diff it against its predecessor.
 */
class ChartDifficultyRevisionService
{
    /**
     * Record a snapshot for a code change.
     *
     * When the difficulty has no snapshots yet the original (pre-change) state
     * is captured first, so the very first change is never lost. Saving code
     * identical to the current state records nothing.
     */
    public function record(
        ChartDifficulty $difficulty,
        string $before,
        string $after,
        ?int $userId,
    ): ?ChartDifficultyRevision {
        if ($before === $after) {
            return null;
        }

        if (! $difficulty->revisions()->exists()) {
            $difficulty->revisions()->create([
                'user_id' => null,
                'code' => $before,
                'is_original' => true,
            ]);
        }

        return $difficulty->revisions()->create([
            'user_id' => $userId,
            'code' => $after,
            'is_original' => false,
        ]);
    }
}
