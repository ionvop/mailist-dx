<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
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
