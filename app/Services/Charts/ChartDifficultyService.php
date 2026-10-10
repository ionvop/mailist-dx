<?php

namespace App\Services\Charts;

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Services\Maidata\MaidataSerializer;
use Illuminate\Support\Facades\Storage;

/**
 * Updates a single difficulty's simai chart code, keeping the stored maidata
 * file and the downloadable package in sync.
 */
class ChartDifficultyService
{
    public function __construct(
        private readonly MaidataSerializer $serializer,
        private readonly ChartAssetService $assets,
    ) {}

    /**
     * Replace the given difficulty's code.
     */
    public function updateCode(Chart $chart, ChartDifficulty $difficulty, string $code): void
    {
        $code = trim($code);

        $difficulty->code = $code;
        $difficulty->save();

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

        if ($chart->download_path !== null) {
            $this->assets->buildDownload($disk, $chart, [
                'maidata_path' => $chart->maidata_path,
                'art_path' => $chart->art_path,
                'track_path' => $chart->track_path,
                'pv_path' => $chart->pv_path,
            ], $chart->download_path);
        }
    }
}
