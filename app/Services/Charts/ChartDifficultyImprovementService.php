<?php

namespace App\Services\Charts;

/**
 * Handles the code-context side of difficulty improvement suggestions.
 *
 * The snippet attached to a suggestion is always extracted server-side from
 * the difficulty's current code using the submitted line range, so the stored
 * context cannot be tampered with and always matches the code it refers to.
 */
class ChartDifficultyImprovementService
{
    /**
     * Extract the snippet for a 1-indexed, inclusive line range.
     *
     * The range is clamped to the bounds of the code, so an out-of-range
     * selection still yields the nearest valid lines. Returns null when no
     * starting line was provided (a general, un-anchored suggestion).
     */
    public function extractSnippet(string $code, ?int $lineStart, ?int $lineEnd = null): ?string
    {
        if ($lineStart === null) {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $code) ?: [''];
        $total = count($lines);

        $start = max(1, min($lineStart, $total));
        $end = max($start, min($lineEnd ?? $lineStart, $total));

        return implode("\n", array_slice($lines, $start - 1, $end - $start + 1));
    }
}
