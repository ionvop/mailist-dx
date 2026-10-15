<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Models\Chart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartLikeController extends Controller
{
    /**
     * Like a chart. Any user who can view the chart may like it.
     */
    public function store(Request $request, Chart $chart): RedirectResponse
    {
        $this->ensureVisible($request, $chart);

        $chart->likes()->syncWithoutDetaching([$request->user()->id]);

        return back()->with('status', 'Chart liked.');
    }

    /**
     * Remove the current user's like from a chart.
     */
    public function destroy(Request $request, Chart $chart): RedirectResponse
    {
        $this->ensureVisible($request, $chart);

        $chart->likes()->detach($request->user()->id);

        return back()->with('status', 'Like removed.');
    }

    /**
     * Abort with a 404 when the chart is not visible to the current user.
     */
    private function ensureVisible(Request $request, Chart $chart): void
    {
        if (! $chart->isVisibleTo($request->user())) {
            throw new NotFoundHttpException;
        }
    }
}
