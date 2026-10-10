<?php

namespace App\Services\Charts;

use App\Models\Chart;
use App\Models\User;
use App\Services\Maidata\MaidataParser;
use App\Services\Maidata\ParsedMaidata;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Orchestrates the chart upload pipeline: extract, parse, store, and persist.
 */
class ChartUploadService
{
    public function __construct(
        private readonly MaidataParser $parser,
        private readonly ChartPackageExtractor $extractor,
        private readonly ChartAssetService $assets,
    ) {}

    /**
     * Process an uploaded chart package and persist the resulting chart.
     *
     * @param  array{description?: string|null, is_private?: bool}  $attributes
     *
     * @throws ChartUploadException
     */
    public function handle(
        User $uploader,
        UploadedFile $package,
        ?UploadedFile $art,
        ?UploadedFile $track,
        ?UploadedFile $pv,
        array $attributes,
    ): Chart {
        $extracted = $this->extractor->extract($package->getRealPath());

        try {
            $maidata = $this->parser->parse(File::get($extracted->maidataPath));

            if ($maidata->logs->hasErrors()) {
                throw new ChartUploadException($maidata->logs->errors);
            }

            return $this->persist($uploader, $extracted, $maidata, $art, $track, $pv, $attributes);
        } finally {
            File::deleteDirectory($extracted->tempDir);
        }
    }

    /**
     * Persist the chart, its files, and its difficulties.
     *
     * @param  array{description?: string|null, is_private?: bool}  $attributes
     */
    private function persist(
        User $uploader,
        ExtractedPackage $extracted,
        ParsedMaidata $maidata,
        ?UploadedFile $art,
        ?UploadedFile $track,
        ?UploadedFile $pv,
        array $attributes,
    ): Chart {
        $disk = Storage::disk('local');

        $chart = new Chart([
            'title' => $maidata->title,
            'title_alias' => $maidata->titleAlias,
            'artist' => $maidata->artist,
            'artist_alias' => $maidata->artistAlias,
            'artist_link' => $maidata->artistLink,
            'illustrator' => $maidata->illustrator,
            'illustrator_link' => $maidata->illustratorLink,
            'note_designer' => $maidata->noteDesigner,
            'audio_bpm' => $maidata->audioBpm,
            'info' => $maidata->info,
            'tags' => $maidata->tags,
            'description' => $attributes['description'] ?? null,
            'is_private' => $attributes['is_private'] ?? false,
        ]);

        $chart->uploader()->associate($uploader);
        $chart->save();

        $directory = "charts/{$chart->id}";

        try {
            $paths = $this->storeFiles($disk, $directory, $extracted, $art, $track, $pv);

            $chart->fill($paths);
            $chart->save();

            $this->storeDifficulties($chart, $maidata);

            $downloadPath = $this->buildDownload($disk, $directory, $chart, $paths);
            $chart->download_path = $downloadPath;
            $chart->save();
        } catch (Throwable $exception) {
            $disk->deleteDirectory($directory);
            $chart->delete();

            throw $exception;
        }

        return $chart;
    }

    /**
     * Store the chart's component files on the disk.
     *
     * @return array<string, string|null>
     */
    private function storeFiles(
        Filesystem $disk,
        string $directory,
        ExtractedPackage $extracted,
        ?UploadedFile $art,
        ?UploadedFile $track,
        ?UploadedFile $pv,
    ): array {
        $maidataPath = "{$directory}/maidata.txt";
        $disk->put($maidataPath, File::get($extracted->maidataPath));

        return [
            'maidata_path' => $maidataPath,
            'art_path' => $this->assets->storeArt($disk, $directory, $art, $extracted->artPath),
            'track_path' => $this->assets->storeMedia($disk, $directory, 'track', $track, $extracted->trackPath),
            'pv_path' => $this->assets->storeMedia($disk, $directory, 'pv', $pv, $extracted->pvPath),
        ];
    }

    /**
     * Persist the difficulties parsed from the maidata file.
     */
    private function storeDifficulties(Chart $chart, ParsedMaidata $maidata): void
    {
        foreach ($maidata->charts as $parsed) {
            $chart->difficulties()->create([
                'chart_index' => $parsed->chartIndex,
                'difficulty' => $parsed->difficulty,
                'difficulty_alias' => $parsed->difficultyAlias,
                'difficulty_color' => $parsed->difficultyColor,
                'difficulty_value' => $parsed->difficultyValue,
                'charter' => $parsed->charter,
                'info' => $parsed->info,
                'code' => $parsed->code,
            ]);
        }
    }

    /**
     * Build the downloadable package and return its stored path.
     *
     * @param  array<string, string|null>  $paths
     */
    private function buildDownload(
        Filesystem $disk,
        string $directory,
        Chart $chart,
        array $paths,
    ): string {
        return $this->assets->buildDownload($disk, $chart, $paths, "{$directory}/download.zip");
    }
}
