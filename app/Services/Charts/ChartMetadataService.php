<?php

namespace App\Services\Charts;

use App\Models\Chart;
use App\Services\Maidata\MaidataParser;
use App\Services\Maidata\MaidataSerializer;
use App\Services\Maidata\ParsedMaidata;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Updates a chart's editable metadata, keeping the stored maidata file and the
 * chart's database columns in sync, and rebuilding the downloadable package.
 */
class ChartMetadataService
{
    /**
     * Map of request field to the maidata header key it is stored under.
     */
    private const MAIDATA_KEYS = [
        'title' => 'title',
        'title_alias' => 'title_alias',
        'artist' => 'artist',
        'artist_alias' => 'artist_alias',
        'artist_link' => 'artist_link',
        'illustrator' => 'illus',
        'illustrator_link' => 'illus_link',
        'note_designer' => 'des',
        'audio_bpm' => 'wholebpm',
        'tags' => 'tags',
        'info' => 'freemsg',
    ];

    public function __construct(
        private readonly MaidataSerializer $serializer,
        private readonly MaidataParser $parser,
        private readonly ChartPackageBuilder $packageBuilder,
    ) {}

    /**
     * Apply the given metadata to the chart.
     *
     * @param  array<string, string|null>  $metadata
     *
     * @throws ChartUploadException when the rewritten maidata fails to parse.
     */
    public function update(Chart $chart, array $metadata, ?string $description): Chart
    {
        $disk = Storage::disk('local');
        $maidataPath = $chart->maidata_path;

        if ($maidataPath !== null && $disk->exists($maidataPath)) {
            $contents = $disk->get($maidataPath);
            $updated = $this->serializer->replaceHeaderValues($contents, $this->toMaidataValues($metadata));

            $parsed = $this->parser->parse($updated);

            if ($parsed->logs->hasErrors()) {
                throw new ChartUploadException($parsed->logs->errors);
            }

            $disk->put($maidataPath, $updated);

            $this->syncColumns($chart, $parsed);
            $this->rebuildDownload($disk, $chart);
        } else {
            $this->applyColumns($chart, $metadata);
        }

        $chart->description = $description;
        $chart->save();

        return $chart;
    }

    /**
     * Apply the editable fields directly to the chart when no maidata exists.
     *
     * @param  array<string, string|null>  $metadata
     */
    private function applyColumns(Chart $chart, array $metadata): void
    {
        $values = [];

        foreach (self::MAIDATA_KEYS as $field => $key) {
            if (! array_key_exists($field, $metadata)) {
                continue;
            }

            $values[$field] = $field === 'tags'
                ? $this->splitTags($metadata[$field])
                : $metadata[$field];
        }

        $chart->fill($values);
    }

    /**
     * Convert the editable request fields into maidata header values.
     *
     * @param  array<string, string|null>  $metadata
     * @return array<string, string|null>
     */
    private function toMaidataValues(array $metadata): array
    {
        $values = [];

        foreach (self::MAIDATA_KEYS as $field => $key) {
            if (! array_key_exists($field, $metadata)) {
                continue;
            }

            $value = $metadata[$field];

            if ($field === 'tags') {
                $value = $this->normalizeTags($value);
            }

            $values[$key] = $value;
        }

        return $values;
    }

    /**
     * Normalise a comma-separated tag string.
     */
    private function normalizeTags(?string $tags): ?string
    {
        $parts = $this->splitTags($tags);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Split a comma-separated tag string into a list of tags.
     *
     * @return list<string>
     */
    private function splitTags(?string $tags): array
    {
        if ($tags === null || trim($tags) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $tags))));
    }

    /**
     * Sync the chart's database columns from freshly parsed maidata.
     */
    private function syncColumns(Chart $chart, ParsedMaidata $maidata): void
    {
        $chart->fill([
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
        ]);
    }

    /**
     * Rebuild the chart's downloadable package from its current files.
     */
    private function rebuildDownload(Filesystem $disk, Chart $chart): void
    {
        if ($chart->download_path === null) {
            return;
        }

        $this->packageBuilder->build(
            $disk->path($chart->download_path),
            [
                'maidata.txt' => $this->diskPath($disk, $chart->maidata_path),
                'bg.jpg' => $this->diskPath($disk, $chart->art_path),
                'track.'.pathinfo((string) $chart->track_path, PATHINFO_EXTENSION) => $this->diskPath($disk, $chart->track_path),
                'pv.'.pathinfo((string) $chart->pv_path, PATHINFO_EXTENSION) => $this->diskPath($disk, $chart->pv_path),
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
    }

    /**
     * Resolve a stored path to its absolute path when the file exists.
     */
    private function diskPath(Filesystem $disk, ?string $path): ?string
    {
        if ($path === null || ! $disk->exists($path)) {
            return null;
        }

        return $disk->path($path);
    }
}
