<?php

namespace App\Http\Controllers\Charts;

use App\Enums\ChartStatus;
use App\Http\Controllers\Controller;
use App\Models\Chart;
use App\Notifications\ChartStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartApprovalController extends Controller
{
    /**
     * Statuses an approver may approve from.
     *
     * @var list<ChartStatus>
     */
    private const APPROVABLE_STATUSES = [ChartStatus::ForReview, ChartStatus::Completed];

    /**
     * Statuses an approver may revert back to For Review.
     *
     * @var list<ChartStatus>
     */
    private const REVERTIBLE_STATUSES = [ChartStatus::Completed, ChartStatus::Approved];

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

        $chart->uploader->notify(new ChartStatusChanged(
            $chart,
            'Chart approved',
            "\"{$chart->title}\" has been approved.",
        ));

        return back()->with('status', 'Chart approved successfully.');
    }

    /**
     * Revert an approved or completed chart back to For Review.
     */
    public function revert(Request $request, Chart $chart): RedirectResponse
    {
        $this->ensureApprover($request, $chart);

        if (! in_array($chart->status, self::REVERTIBLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Only charts that are completed or approved can be reverted.',
            ]);
        }

        $wasApproved = $chart->status === ChartStatus::Approved;

        $chart->status = ChartStatus::ForReview;
        $chart->save();

        $chart->uploader->notify(new ChartStatusChanged(
            $chart,
            $wasApproved ? 'Chart approval reverted' : 'Chart reverted to For Review',
            "\"{$chart->title}\" was reverted to For Review.",
        ));

        return back()->with('status', $wasApproved
            ? 'Chart approval reverted successfully.'
            : 'Chart reverted to For Review successfully.');
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
