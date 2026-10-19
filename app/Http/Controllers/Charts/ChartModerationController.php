<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Models\Chart;
use App\Notifications\ChartDelisted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartModerationController extends Controller
{
    /**
     * Delist a chart, hiding it from public listings. The chart owner cannot
     * undo this; only moderators may restore the chart.
     */
    public function delist(Request $request, Chart $chart): RedirectResponse
    {
        $this->ensureModerator($request, $chart);

        if ($chart->is_delisted) {
            return back()->with('status', 'Chart is already delisted.');
        }

        $chart->is_delisted = true;
        $chart->save();

        $chart->uploader->notify(new ChartDelisted(
            $chart,
            'Chart delisted',
            "\"{$chart->title}\" has been delisted by a moderator and is no longer publicly visible.",
        ));

        return back()->with('status', 'Chart delisted successfully.');
    }

    /**
     * Restore a delisted chart so it becomes publicly visible again.
     */
    public function restore(Request $request, Chart $chart): RedirectResponse
    {
        $this->ensureModerator($request, $chart);

        if (! $chart->is_delisted) {
            return back()->with('status', 'Chart is not delisted.');
        }

        $chart->is_delisted = false;
        $chart->save();

        $chart->uploader->notify(new ChartDelisted(
            $chart,
            'Chart restored',
            "\"{$chart->title}\" has been restored by a moderator and is publicly visible again.",
        ));

        return back()->with('status', 'Chart restored successfully.');
    }

    /**
     * Abort with a 404 when the current user may not moderate this chart.
     */
    private function ensureModerator(Request $request, Chart $chart): void
    {
        if (! $chart->canModerate($request->user())) {
            throw new NotFoundHttpException;
        }
    }
}
