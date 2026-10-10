<?php

namespace App\Services\Charts;

use App\Models\Chart;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * Stores and deletes a chart's component files (cover, track, PV) and rebuilds
 * its downloadable package. Shared by the upload, metadata, and asset replace
 * flows so their file handling stays consistent.
 */
class ChartAssetService
{
    public function __construct(
        private readonly ChartImageProcessor $imageProcessor,
        private readonly ChartPackageBuilder $packageBuilder,
    ) {}

    /**
     * Store the chart cover, processing it into a square image when possible.
     */
    public function storeArt(Filesystem $disk, string $directory, ?UploadedFile $art, ?string $extractedArt): ?string
    {
        $source = $art?->getRealPath() ?? $extractedArt;

        if ($source === null) {
            return null;
        }

        $destination = "{$directory}/art.jpg";
        $this->imageProcessor->process($source, $disk->path($destination));

        return $destination;
    }

    /**
     * Store a media file, preferring a manual override over the packaged file.
     */
    public function storeMedia(
        Filesystem $disk,
        string $directory,
        string $name,
        ?UploadedFile $override,
        ?string $extracted,
    ): ?string {
        if ($override !== null) {
            $source = $override->getRealPath();
            $extension = strtolower($override->getClientOriginalExtension());
        } elseif ($extracted !== null) {
            $source = $extracted;
            $extension = strtolower(pathinfo($extracted, PATHINFO_EXTENSION));
        } else {
            return null;
        }

        $extension = $extension !== '' ? $extension : 'bin';
        $destination = "{$directory}/{$name}.{$extension}";

        $disk->put($destination, File::get($source));

        return $destination;
    }

    /**
     * Delete a stored file when it exists.
     */
    public function delete(Filesystem $disk, ?string $path): void
    {
        if ($path !== null && $disk->exists($path)) {
            $disk->delete($path);
        }
    }

    /**
     * Delete a replaced asset when its old path is no longer the current one.
     */
    public function deleteReplaced(Filesystem $disk, ?string $oldPath, ?string $newPath): void
    {
        if ($oldPath !== null && $oldPath !== $newPath) {
            $this->delete($disk, $oldPath);
        }
    }

    /**
     * Rebuild the chart's downloadable package from the given file paths.
     *
     * @param  array<string, string|null>  $paths  Map of component key to stored path.
     */
    public function buildDownload(Filesystem $disk, Chart $chart, array $paths, string $destination): string
    {
        $this->packageBuilder->build(
            $disk->path($destination),
            [
                'maidata.txt' => $this->absolutePath($disk, $paths['maidata_path'] ?? null),
                'bg.jpg' => $this->absolutePath($disk, $paths['art_path'] ?? null),
                'track.'.pathinfo((string) ($paths['track_path'] ?? null), PATHINFO_EXTENSION) => $this->absolutePath($disk, $paths['track_path'] ?? null),
                'pv.'.pathinfo((string) ($paths['pv_path'] ?? null), PATHINFO_EXTENSION) => $this->absolutePath($disk, $paths['pv_path'] ?? null),
            ],
            [
                'version' => 'v1',
                'server' => config('app.url'),
                'chart_id' => $chart->id,
                'difficulties' => $chart->difficulties->map(fn ($difficulty) => [
                    'id' => $difficulty->id,
                    'chart_index' => $difficulty->chart_index,
                ])->all(),
            ],
        );

        return $destination;
    }

    /**
     * Resolve a stored path to its absolute path when the file exists.
     */
    private function absolutePath(Filesystem $disk, ?string $path): ?string
    {
        if ($path === null || ! $disk->exists($path)) {
            return null;
        }

        return $disk->path($path);
    }
}
