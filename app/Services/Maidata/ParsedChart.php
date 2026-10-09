<?php

namespace App\Services\Maidata;

use App\Enums\ChartDifficultyName;

/**
 * A single difficulty parsed from a maidata file.
 */
class ParsedChart
{
    public function __construct(
        public readonly string $chartIndex,
        public readonly ChartDifficultyName $difficulty,
        public readonly ?string $difficultyAlias,
        public readonly ?string $difficultyColor,
        public readonly ?string $difficultyValue,
        public readonly ?string $charter,
        public readonly ?string $info,
        public readonly string $code,
    ) {}
}
