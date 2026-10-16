<?php

namespace App\Http\Controllers\Charts;

use App\Enums\ChartDifficultyName;
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

        $chart->load(['uploader', 'difficulties'])->loadCount('likes');

        $existingTypes = $chart->difficulties->pluck('difficulty')
            ->map(fn (ChartDifficultyName $name) => $name->value);

        $availableDifficulties = collect(ChartDifficultyName::cases())
            ->reject(fn (ChartDifficultyName $name) => $name === ChartDifficultyName::Custom)
            ->reject(fn (ChartDifficultyName $name) => $existingTypes->contains($name->value))
            ->values();

        return view('pages.charts.show', [
            'chart' => $chart,
            'isLiked' => $chart->isLikedBy($request->user()),
            'availableDifficulties' => $availableDifficulties,
        ]);
    }
}
