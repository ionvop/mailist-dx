<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\UpdateChartDifficultyRequest;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyRevision;
use App\Services\Charts\ChartDifficultyRevisionService;
use App\Services\Charts\ChartDifficultyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartDifficultyController extends Controller
{
    /**
     * Tabs available on the difficulty page.
     */
    private const TABS = ['code', 'improvements', 'comments', 'history', 'settings'];

    /**
     * Number of revisions shown per page on the history tab.
     */
    private const REVISIONS_PER_PAGE = 20;

    public function __construct(
        private readonly ChartDifficultyService $difficulties,
        private readonly ChartDifficultyRevisionService $revisions,
    ) {}

    /**
     * Show a chart difficulty. Defaults to the code tab.
     */
    public function show(Request $request, Chart $chart, ChartDifficulty $difficulty, ?string $tab = null): View
    {
        $this->ensureVisible($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);

        $tab = in_array($tab, self::TABS, true) ? $tab : 'code';

        if ($tab === 'settings' && ! $chart->isOwnedBy($request->user())) {
            throw new NotFoundHttpException;
        }

        $chart->load(['uploader', 'difficulties']);

        $canEdit = $chart->canEdit($request->user());

        $focusRevisionId = $tab === 'history' ? $this->focusRevisionId($request, $difficulty) : null;

        return view('pages.charts.difficulty', [
            'chart' => $chart,
            'difficulty' => $difficulty,
            'activeTab' => $tab,
            'canEdit' => $canEdit,
            'preview' => $this->previewConfig($chart, $difficulty),
            'focusRevisionId' => $focusRevisionId,
            'revisions' => $tab === 'history'
                ? $difficulty->revisions()->with('user')->paginate(
                    self::REVISIONS_PER_PAGE,
                    ['*'],
                    'page',
                    $this->revisionPage($difficulty, $focusRevisionId),
                )
                : null,
            'improvements' => $tab === 'improvements'
                ? $difficulty->improvements()->with(['user', 'resolver', 'revision.user'])->get()
                : null,
            'comments' => $tab === 'comments'
                ? $difficulty->comments()->with('user')->get()
                : null,
            'revisionOptions' => $tab === 'improvements' && $canEdit
                ? $difficulty->revisions()->with('user')->get()
                : null,
        ]);
    }

    /**
     * The revision id the history tab should jump to and expand, if any.
     */
    private function focusRevisionId(Request $request, ChartDifficulty $difficulty): ?int
    {
        $revisionId = $request->query('revision');

        if (! is_numeric($revisionId)) {
            return null;
        }

        $revisionId = (int) $revisionId;

        $exists = $difficulty->revisions()->whereKey($revisionId)->exists();

        return $exists ? $revisionId : null;
    }

    /**
     * The pagination page that contains the given revision, defaulting to 1.
     *
     * Revisions are listed newest-first, so the offset counts how many later
     * revisions exist before the target.
     */
    private function revisionPage(ChartDifficulty $difficulty, ?int $revisionId): int
    {
        if ($revisionId === null) {
            return 1;
        }

        $offset = $difficulty->revisions()->where('id', '>', $revisionId)->count();

        return intdiv($offset, self::REVISIONS_PER_PAGE) + 1;
    }

    /**
     * Build the payload for the WebGL chart preview, or null when the chart has
     * no stored maidata file for the preview engine to read.
     *
     * The build is handed the public file URLs (it fetches them itself) plus a
     * level token derived from the difficulty's chart index, e.g. index 5 -> lv4.
     *
     * @return array{maidata: string, track: string|null, art: string|null, pv: string|null, level: string}|null
     */
    private function previewConfig(Chart $chart, ChartDifficulty $difficulty): ?array
    {
        if ($chart->maidata_path === null) {
            return null;
        }

        $file = fn (string $type): string => route('charts.files', [$chart, $type]);

        return [
            'maidata' => $file('maidata'),
            'track' => $chart->track_path !== null ? $file('track') : null,
            'art' => $chart->art_path !== null ? $file('art') : null,
            'pv' => $chart->pv_path !== null ? $file('pv') : null,
            'level' => 'lv'.((int) $difficulty->chart_index - 1),
        ];
    }

    /**
     * Update a difficulty's simai chart code. Editors only.
     */
    public function update(UpdateChartDifficultyRequest $request, Chart $chart, ChartDifficulty $difficulty): RedirectResponse
    {
        $this->ensureEditable($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);

        $this->difficulties->updateCode($chart, $difficulty, $request->validated('code'), $request->user()?->id);

        return redirect()
            ->route('charts.difficulties.show', [$chart, $difficulty])
            ->with('status', 'Chart code updated successfully.');
    }

    /**
     * Restore a difficulty to an earlier revision. Editors only.
     *
     * The current code is snapshotted before reverting, so history is only
     * ever appended to, never rewritten.
     */
    public function restore(Request $request, Chart $chart, ChartDifficulty $difficulty, ChartDifficultyRevision $revision): RedirectResponse
    {
        $this->ensureEditable($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);
        $this->ensureRevisionBelongsToDifficulty($difficulty, $revision);

        $this->difficulties->updateCode($chart, $difficulty, $revision->code, $request->user()?->id);

        return redirect()
            ->route('charts.difficulties.show', [$chart, $difficulty])
            ->with('status', 'Restored the selected version.');
    }

    /**
     * Return a revision and its predecessor for the client-side diff viewer.
     */
    public function diff(Request $request, Chart $chart, ChartDifficulty $difficulty, ChartDifficultyRevision $revision): JsonResponse
    {
        $this->ensureVisible($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);
        $this->ensureRevisionBelongsToDifficulty($difficulty, $revision);

        $revision->loadMissing('user');

        $previous = $difficulty->revisions()
            ->where('id', '<', $revision->id)
            ->with('user')
            ->first();

        return response()->json([
            'current' => $this->revisionPayload($revision),
            'previous' => $previous === null ? null : $this->revisionPayload($previous),
        ]);
    }

    /**
     * Shape a revision for the diff viewer JSON response.
     *
     * @return array{id: int, code: string, is_original: bool, author: string|null, created_at: string|null}
     */
    private function revisionPayload(ChartDifficultyRevision $revision): array
    {
        return [
            'id' => $revision->id,
            'code' => $revision->code,
            'is_original' => $revision->is_original,
            'author' => $revision->user?->username,
            'created_at' => $revision->created_at?->utc()->toIso8601String(),
        ];
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
     * Abort with a 404 when the revision does not belong to the difficulty.
     */
    private function ensureRevisionBelongsToDifficulty(ChartDifficulty $difficulty, ChartDifficultyRevision $revision): void
    {
        if ($revision->difficulty_id !== $difficulty->id) {
            throw new NotFoundHttpException;
        }
    }
}
