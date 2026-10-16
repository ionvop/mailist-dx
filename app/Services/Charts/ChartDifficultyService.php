<?php

namespace App\Services\Charts;

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Services\Maidata\MaidataParser;
use App\Services\Maidata\MaidataSerializer;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Updates a single difficulty's simai chart code, keeping the stored maidata
 * file and the downloadable package in sync.
 */
class ChartDifficultyService
{
    public function __construct(
        private readonly MaidataSerializer $serializer,
        private readonly MaidataParser $parser,
        private readonly ChartAssetService $assets,
        private readonly ChartDifficultyRevisionService $revisions,
    ) {}

    /**
     * Replace the given difficulty's code.
     *
     * A revision is snapshotted when the code actually changes (see
     * {@see ChartDifficultyRevisionService::record()}).
     */
    public function updateCode(Chart $chart, ChartDifficulty $difficulty, string $code, ?int $userId = null): void
    {
        $code = trim($code);
        $before = $difficulty->code;

        $difficulty->code = $code;
        $difficulty->save();

        $this->revisions->record($difficulty, $before, $code, $userId);

        $disk = Storage::disk('local');
        $maidataPath = $chart->maidata_path;

        if ($maidataPath === null || ! $disk->exists($maidataPath)) {
            return;
        }

        $disk->put($maidataPath, $this->serializer->replaceChartCode(
            $disk->get($maidataPath),
            $difficulty->chart_index,
            $code,
        ));

        $this->rebuildDownload($disk, $chart);
    }

    /**
     * Update the editable metadata (level value and charter) of a difficulty.
     *
     * When the chart has a stored maidata file the matching `&lv_#=` and
     * `&des_#=` headers are rewritten in place and the downloadable package is
     * rebuilt. A missing value removes the corresponding header. The difficulty
     * columns are synced from the freshly parsed maidata so the database and the
     * file never drift apart.
     *
     * @param  array{difficulty_value?: string|null, charter?: string|null}  $metadata
     *
     * @throws ChartUploadException when the rewritten maidata fails to parse.
     */
    public function updateMetadata(Chart $chart, ChartDifficulty $difficulty, array $metadata): void
    {
        $values = [
            'difficulty_value' => $metadata['difficulty_value'] ?? null,
            'charter' => $metadata['charter'] ?? null,
        ];

        $disk = Storage::disk('local');
        $maidataPath = $chart->maidata_path;

        if ($maidataPath === null || ! $disk->exists($maidataPath)) {
            $difficulty->fill($values)->save();

            return;
        }

        $updated = $this->serializer->replaceHeaderValues($disk->get($maidataPath), [
            "lv_{$difficulty->chart_index}" => $values['difficulty_value'],
            "des_{$difficulty->chart_index}" => $values['charter'],
        ]);

        $parsed = $this->parser->parse($updated);

        if ($parsed->logs->hasErrors()) {
            throw new ChartUploadException($parsed->logs->errors);
        }

        $parsedChart = collect($parsed->charts)->firstWhere('chartIndex', $difficulty->chart_index);

        $difficulty->fill([
            'difficulty_value' => $parsedChart?->difficultyValue,
            'charter' => $parsedChart?->charter,
        ])->save();

        $disk->put($maidataPath, $updated);

        $this->rebuildDownload($disk, $chart);
    }

    /**
     * Delete a difficulty, removing its headers from the maidata file and
     * rebuilding the downloadable package.
     *
     * The difficulty's revisions, improvements, and comments are removed by the
     * database's cascading foreign keys.
     */
    public function delete(Chart $chart, ChartDifficulty $difficulty): void
    {
        $disk = Storage::disk('local');
        $maidataPath = $chart->maidata_path;

        if ($maidataPath !== null && $disk->exists($maidataPath)) {
            $disk->put($maidataPath, $this->serializer->removeChart(
                $disk->get($maidataPath),
                $difficulty->chart_index,
            ));
        }

        $difficulty->delete();

        $this->rebuildDownload($disk, $chart);
    }

    /**
     * Rebuild the chart's downloadable package from its current files.
     */
    private function rebuildDownload(Filesystem $disk, Chart $chart): void
    {
        if ($chart->download_path === null) {
            return;
        }

        $this->assets->buildDownload($disk, $chart, [
            'maidata_path' => $chart->maidata_path,
            'art_path' => $chart->art_path,
            'track_path' => $chart->track_path,
            'pv_path' => $chart->pv_path,
        ], $chart->download_path);
    }
}
