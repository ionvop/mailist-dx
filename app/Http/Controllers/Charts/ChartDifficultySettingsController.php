<?php

namespace App\Http\Controllers\Charts;

use App\Enums\ChartDifficultyName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\StoreChartDifficultyRequest;
use App\Http\Requests\Charts\UpdateChartDifficultySettingsRequest;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Services\Charts\ChartDifficultyService;
use App\Services\Charts\ChartUploadException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartDifficultySettingsController extends Controller
{
    public function __construct(
        private readonly ChartDifficultyService $difficulties,
    ) {}

    /**
     * Add a difficulty to a chart. Owner only.
     *
     * The dropdown only offers difficulty types the chart does not have yet;
     * the level starts as "-" and the charter falls back to the chart's note
     * designer, both editable later in the difficulty's Settings tab.
     */
    public function store(StoreChartDifficultyRequest $request, Chart $chart): RedirectResponse
    {
        $this->ensureOwner($request, $chart);

        try {
            $this->difficulties->create($chart, ChartDifficultyName::from($request->validated('difficulty')));
        } catch (ChartUploadException $exception) {
            return back()
                ->withInput()
                ->withErrors(['difficulty' => $exception->errors]);
        }

        return redirect()
            ->route('charts.show', $chart)
            ->with('status', 'Difficulty added successfully.');
    }

    /**
     * Update a difficulty's level value and charter. Owner only.
     */
    public function update(UpdateChartDifficultySettingsRequest $request, Chart $chart, ChartDifficulty $difficulty): RedirectResponse
    {
        $this->ensureOwner($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);

        try {
            $this->difficulties->updateMetadata($chart, $difficulty, $request->validated());
        } catch (ChartUploadException $exception) {
            return back()
                ->withInput()
                ->withErrors(['difficulty_value' => $exception->errors]);
        }

        return redirect()
            ->route('charts.difficulties.show', [$chart, $difficulty, 'settings'])
            ->with('status', 'Difficulty settings updated successfully.');
    }

    /**
     * Delete a difficulty. Owner only.
     *
     * The chart must keep at least one difficulty, so deleting the last one is
     * refused. Otherwise the difficulty's headers are stripped from the maidata
     * file and the downloadable package is rebuilt.
     */
    public function destroy(Request $request, Chart $chart, ChartDifficulty $difficulty): RedirectResponse
    {
        $this->ensureOwner($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);

        if ($chart->difficulties()->count() <= 1) {
            return redirect()
                ->route('charts.difficulties.show', [$chart, $difficulty, 'settings'])
                ->withErrors(['difficulty' => 'A chart must keep at least one difficulty.']);
        }

        $name = $difficulty->displayName();

        $this->difficulties->delete($chart, $difficulty);

        return redirect()
            ->route('charts.show', $chart)
            ->with('status', "“{$name}” has been deleted.");
    }

    /**
     * Abort with a 404 when the chart is not owned by the current user.
     */
    private function ensureOwner(Request $request, Chart $chart): void
    {
        if (! $chart->isOwnedBy($request->user())) {
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
