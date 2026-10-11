<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\UpdateChartDifficultyRequest;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Services\Charts\ChartDifficultyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartDifficultyController extends Controller
{
    /**
     * Tabs available on the difficulty page.
     */
    private const TABS = ['code', 'improvements', 'comments', 'history'];

    public function __construct(
        private readonly ChartDifficultyService $difficulties,
    ) {}

    /**
     * Show a chart difficulty. Defaults to the code tab.
     */
    public function show(Request $request, Chart $chart, ChartDifficulty $difficulty, ?string $tab = null): View
    {
        $this->ensureVisible($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);

        $tab = in_array($tab, self::TABS, true) ? $tab : 'code';

        $chart->load(['uploader', 'difficulties']);

        return view('pages.charts.difficulty', [
            'chart' => $chart,
            'difficulty' => $difficulty,
            'activeTab' => $tab,
            'canEdit' => $chart->canEdit($request->user()),
            'preview' => $this->previewConfig($chart, $difficulty),
        ]);
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

        $this->difficulties->updateCode($chart, $difficulty, $request->validated('code'));

        return redirect()
            ->route('charts.difficulties.show', [$chart, $difficulty])
            ->with('status', 'Chart code updated successfully.');
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
}
