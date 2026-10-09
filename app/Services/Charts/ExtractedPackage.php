<?php

namespace App\Services\Charts;

/**
 * The files discovered inside an extracted chart package.
 */
class ExtractedPackage
{
    public function __construct(
        public readonly string $tempDir,
        public readonly string $maidataPath,
        public readonly ?string $artPath,
        public readonly ?string $trackPath,
        public readonly ?string $pvPath,
    ) {}
}
