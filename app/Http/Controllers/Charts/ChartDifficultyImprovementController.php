<?php

namespace App\Http\Controllers\Charts;

use App\Enums\ImprovementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\StoreImprovementRequest;
use App\Http\Requests\Charts\UpdateImprovementRequest;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyImprovement;
use App\Services\Charts\ChartDifficultyImprovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartDifficultyImprovementController extends Controller
{
    public function __construct(
        private readonly ChartDifficultyImprovementService $improvements,
    ) {}

    /**
     * Suggest an improvement for a difficulty. Any user who can view the chart
     * may post a suggestion.
     */
    public function store(StoreImprovementRequest $request, Chart $chart, ChartDifficulty $difficulty): RedirectResponse
    {
        $this->ensureVisible($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);

        $validated = $request->validated();
        $lineStart = $validated['line_start'] ?? null;
        $lineEnd = $validated['line_end'] ?? null;

        $difficulty->improvements()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'line_start' => $lineStart,
            'line_end' => $lineEnd,
            'snippet' => $this->improvements->extractSnippet($difficulty->code, $lineStart, $lineEnd),
        ]);

        return redirect()
            ->route('charts.difficulties.show', [$chart, $difficulty, 'improvements'])
            ->with('status', 'Your suggestion has been posted.');
    }

    /**
     * Resolve or reject a suggestion. Editors only.
     */
    public function update(UpdateImprovementRequest $request, Chart $chart, ChartDifficulty $difficulty, ChartDifficultyImprovement $improvement): RedirectResponse
    {
        $this->ensureEditable($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);
        $this->ensureImprovementBelongsToDifficulty($difficulty, $improvement);

        $validated = $request->validated();
        $revisionId = $validated['revision_id'] ?? null;

        if ($revisionId !== null) {
            $this->ensureRevisionBelongsToDifficulty($difficulty, $revisionId);
        }

        $improvement->update([
            'status' => $validated['status'],
            'resolution_note' => $validated['resolution_note'],
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
            'revision_id' => $revisionId,
        ]);

        return back()->with('status', 'The suggestion has been updated.');
    }

    /**
     * Revoke a resolution, reopening the suggestion. Editors only.
     */
    public function revoke(Request $request, Chart $chart, ChartDifficulty $difficulty, ChartDifficultyImprovement $improvement): RedirectResponse
    {
        $this->ensureEditable($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);
        $this->ensureImprovementBelongsToDifficulty($difficulty, $improvement);

        $improvement->update([
            'status' => ImprovementStatus::Open,
            'resolution_note' => null,
            'resolved_by' => null,
            'resolved_at' => null,
            'revision_id' => null,
        ]);

        return back()->with('status', 'The resolution has been revoked.');
    }

    /**
     * Delete a suggestion. Only the author may delete their own suggestion.
     */
    public function destroy(Request $request, Chart $chart, ChartDifficulty $difficulty, ChartDifficultyImprovement $improvement): RedirectResponse
    {
        $this->ensureVisible($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);
        $this->ensureImprovementBelongsToDifficulty($difficulty, $improvement);

        abort_unless($improvement->user_id === $request->user()->id, 403);

        $improvement->delete();

        return back()->with('status', 'Your suggestion has been deleted.');
    }

    /**
     * Abort with a 404 when the chart cannot be viewed by the current user.
     */
    private function ensureVisible(Request $request, Chart $chart): void
    {
        if (! $chart->isVisibleTo($request->user())) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Abort with a 404 when the chart cannot be edited by the current user.
     */
    private function ensureEditable(Request $request, Chart $chart): void
    {
        if (! $chart->canEdit($request->user())) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Abort with a 404 when the difficulty does not belong to the chart.
     */
    private function ensureBelongsToChart(Chart $chart, ChartDifficulty $difficulty): void
    {
        if ($difficulty->chart_id !== $chart->id) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Abort with a 404 when the suggestion does not belong to the difficulty.
     */
    private function ensureImprovementBelongsToDifficulty(ChartDifficulty $difficulty, ChartDifficultyImprovement $improvement): void
    {
        if ($improvement->difficulty_id !== $difficulty->id) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Abort with a 404 when the revision does not belong to the difficulty.
     */
    private function ensureRevisionBelongsToDifficulty(ChartDifficulty $difficulty, int $revisionId): void
    {
        if (! $difficulty->revisions()->whereKey($revisionId)->exists()) {
            throw new NotFoundHttpException;
        }
    }
}
