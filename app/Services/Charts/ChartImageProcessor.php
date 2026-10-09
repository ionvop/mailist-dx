<?php

namespace App\Services\Charts;

use GdImage;
use Illuminate\Support\Facades\File;

/**
 * Produces a square chart cover image using the GD extension.
 *
 * When GD is unavailable or the source cannot be decoded, the original file is
 * copied through unchanged so uploads never fail on image processing.
 */
class ChartImageProcessor
{
    /**
     * The width and height of the generated cover, in pixels.
     */
    private const SIZE = 500;

    /**
     * Process the source image into a square cover written to the destination.
     *
     * @return string The path to the written file.
     */
    public function process(string $sourcePath, string $destinationPath): string
    {
        File::ensureDirectoryExists(dirname($destinationPath));

        $image = $this->load($sourcePath);

        if (! $image instanceof GdImage) {
            File::copy($sourcePath, $destinationPath);

            return $destinationPath;
        }

        $cover = $this->makeSquare($image);
        imagedestroy($image);

        imagejpeg($cover, $destinationPath, 90);
        imagedestroy($cover);

        return $destinationPath;
    }

    /**
     * Load an image from disk, returning null when it cannot be decoded.
     */
    private function load(string $path): ?GdImage
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        return $image instanceof GdImage ? $image : null;
    }

    /**
     * Crop and scale the image into a square canvas.
     */
    private function makeSquare(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);

        $sourceX = (int) (($width - $side) / 2);
        $sourceY = (int) (($height - $side) / 2);

        $cover = imagecreatetruecolor(self::SIZE, self::SIZE);

        imagecopyresampled(
            $cover,
            $image,
            0,
            0,
            $sourceX,
            $sourceY,
            self::SIZE,
            self::SIZE,
            $side,
            $side,
        );

        return $cover;
    }
}
