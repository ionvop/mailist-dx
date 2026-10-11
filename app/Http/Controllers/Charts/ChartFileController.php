<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Models\Chart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
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
        'adx' => 'download_path',
    ];

    /**
     * Map of file type to the extension used for the downloaded package.
     *
     * `.zip` and `.adx` are the same archive; only the extension differs.
     */
    private const DOWNLOAD_EXTENSIONS = [
        'download' => 'zip',
        'adx' => 'adx',
    ];

    /**
     * Serve one of a chart's files from the private disk.
     */
    public function show(Request $request, Chart $chart, string $type): Response
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

        if (isset(self::DOWNLOAD_EXTENSIONS[$type])) {
            return $disk->download($path, "{$chart->title}.".self::DOWNLOAD_EXTENSIONS[$type]);
        }

        // A BinaryFileResponse (unlike a StreamedResponse) honours the Range
        // header, so media files can be seeked forward in the browser player.
        return new BinaryFileResponse($disk->path($path), 200, [
            'Content-Type' => $disk->mimeType($path),
        ]);
    }
}
