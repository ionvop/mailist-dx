<?php

namespace App\Services\Charts;

use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Extracts a `.zip`/`.adx` chart package and locates its component files.
 */
class ChartPackageExtractor
{
    /**
     * Image extensions recognised for the chart background.
     */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Audio extensions recognised for the chart track.
     */
    private const AUDIO_EXTENSIONS = ['mp3', 'ogg', 'wav', 'm4a'];

    /**
     * Video extensions recognised for the chart PV.
     */
    private const VIDEO_EXTENSIONS = ['mp4', 'webm'];

    /**
     * Extract the package at the given path into a fresh temporary directory.
     *
     * @throws ChartUploadException when the archive cannot be opened or has no maidata file.
     */
    public function extract(string $archivePath): ExtractedPackage
    {
        $zip = new ZipArchive;
        $opened = $zip->open($archivePath);

        if ($opened !== true) {
            throw new ChartUploadException(['The uploaded package could not be opened.']);
        }

        $tempDir = $this->makeTempDir();

        $zip->extractTo($tempDir);
        $zip->close();

        $maidataPath = $this->findMaidata($tempDir);

        if ($maidataPath === null) {
            File::deleteDirectory($tempDir);

            throw new ChartUploadException(['The package does not contain a maidata.txt file.']);
        }

        // The maidata directory is the working directory for the other components.
        $workingDir = dirname($maidataPath);

        return new ExtractedPackage(
            tempDir: $tempDir,
            maidataPath: $maidataPath,
            artPath: $this->findByBasename($workingDir, 'bg', self::IMAGE_EXTENSIONS),
            trackPath: $this->findByBasename($workingDir, 'track', self::AUDIO_EXTENSIONS),
            pvPath: $this->findByBasename($workingDir, 'pv', self::VIDEO_EXTENSIONS),
        );
    }

    /**
     * Recursively locate a `maidata.txt` file within the extracted directory.
     */
    private function findMaidata(string $dir): ?string
    {
        foreach (File::allFiles($dir) as $file) {
            if (strtolower($file->getFilename()) === 'maidata.txt') {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Find a file whose basename matches the given name and one of the extensions.
     *
     * @param  list<string>  $extensions
     */
    private function findByBasename(string $dir, string $basename, array $extensions): ?string
    {
        foreach (File::files($dir) as $file) {
            $name = strtolower($file->getFilenameWithoutExtension());
            $extension = strtolower($file->getExtension());

            if ($name === $basename && in_array($extension, $extensions, true)) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Create a unique temporary directory.
     */
    private function makeTempDir(): string
    {
        $dir = storage_path('app/chart-uploads/'.bin2hex(random_bytes(16)));

        File::ensureDirectoryExists($dir);

        return $dir;
    }
}
