<?php

namespace App\Http\Controllers\Charts;

use App\Enums\ChartStatus;
use App\Http\Controllers\Controller;
use App\Models\Chart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartApprovalController extends Controller
{
    /**
     * Statuses an approver may approve from, or revert an approved chart to.
     *
     * @var list<ChartStatus>
     */
    private const APPROVABLE_STATUSES = [ChartStatus::ForReview, ChartStatus::Completed];

    /**
     * Approve a chart. Approvers only, and never their own charts.
     */
    public function approve(Request $request, Chart $chart): RedirectResponse
    {
        $this->ensureApprover($request, $chart);

        if (! in_array($chart->status, self::APPROVABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Only charts that are for review or completed can be approved.',
            ]);
        }

        $chart->status = ChartStatus::Approved;
        $chart->save();

        return back()->with('status', 'Chart approved successfully.');
    }

    /**
     * Revert an approved chart back to a reviewable status.
     */
    public function revert(Request $request, Chart $chart): RedirectResponse
    {
        $this->ensureApprover($request, $chart);

        if (! $chart->isApproved()) {
            throw ValidationException::withMessages([
                'status' => 'Only approved charts can be reverted.',
            ]);
        }

        $chart->status = ChartStatus::ForReview;
        $chart->save();

        return back()->with('status', 'Chart approval reverted successfully.');
    }

    /**
     * Abort with a 404 when the current user may not approve this chart.
     */
    private function ensureApprover(Request $request, Chart $chart): void
    {
        if (! $chart->canApprove($request->user())) {
            throw new NotFoundHttpException;
        }
    }
}
