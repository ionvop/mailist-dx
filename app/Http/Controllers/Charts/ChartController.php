<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Models\Chart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartController extends Controller
{
    /**
     * Show a chart's details.
     */
    public function show(Request $request, Chart $chart): View
    {
        if (! $chart->isVisibleTo($request->user())) {
            throw new NotFoundHttpException;
        }

        $chart->load(['uploader', 'difficulties']);

        return view('pages.charts.show', [
            'chart' => $chart,
        ]);
    }
}
