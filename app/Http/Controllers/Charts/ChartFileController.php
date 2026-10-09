<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Models\Chart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartFileController extends Controller
{
    /**
     * Map of file type to the chart column holding its path.
     */
    private const PATHS = [
        'art' => 'art_path',
        'track' => 'track_path',
        'pv' => 'pv_path',
        'maidata' => 'maidata_path',
        'download' => 'download_path',
    ];

    /**
     * Serve one of a chart's files from the private disk.
     */
    public function show(Request $request, Chart $chart, string $type): StreamedResponse
    {
        if (! array_key_exists($type, self::PATHS)) {
            throw new NotFoundHttpException;
        }

        if (! $chart->isVisibleTo($request->user())) {
            throw new NotFoundHttpException;
        }

        $path = $chart->{self::PATHS[$type]};

        if ($path === null || ! Storage::disk('local')->exists($path)) {
            throw new NotFoundHttpException;
        }

        $disk = Storage::disk('local');

        if ($type === 'download') {
            return $disk->download($path, "{$chart->title}.zip");
        }

        return $disk->response($path);
    }
}
