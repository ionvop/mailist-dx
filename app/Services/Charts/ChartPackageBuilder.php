<?php

namespace App\Services\Charts;

use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Builds a downloadable `.zip` package for a chart.
 */
class ChartPackageBuilder
{
    /**
     * Build a zip archive at the destination from the given files.
     *
     * @param  array<string, string|null>  $files  Map of entry name to source path.
     * @param  array<string, mixed>  $manifest  Manifest written as `adxweb.json`.
     */
    public function build(string $destinationPath, array $files, array $manifest): string
    {
        File::ensureDirectoryExists(dirname($destinationPath));

        $zip = new ZipArchive;
        $zip->open($destinationPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $entryName => $sourcePath) {
            if ($sourcePath !== null && File::exists($sourcePath)) {
                $zip->addFile($sourcePath, $entryName);
            }
        }

        $zip->addFromString(
            'adxweb.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
        );

        $zip->close();

        return $destinationPath;
    }
}
