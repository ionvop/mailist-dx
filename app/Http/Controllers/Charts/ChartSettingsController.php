<?php

namespace App\Http\Controllers\Charts;

use App\Enums\ChartStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\UpdateChartMetadataRequest;
use App\Models\Chart;
use App\Services\Charts\ChartMetadataService;
use App\Services\Charts\ChartUploadException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartSettingsController extends Controller
{
    public function __construct(private readonly ChartMetadataService $metadata) {}

    /**
     * Show the chart settings page. Owner only.
     */
    public function edit(Request $request, Chart $chart): View
    {
        $this->ensureOwner($request, $chart);

        $chart->load('difficulties');

        return view('pages.charts.settings', [
            'chart' => $chart,
        ]);
    }

    /**
     * Update the chart's metadata. Owner only.
     */
    public function update(UpdateChartMetadataRequest $request, Chart $chart): RedirectResponse
    {
        $this->ensureOwner($request, $chart);

        $validated = $request->validated();

        try {
            $this->metadata->update($chart, $validated, $validated['description'] ?? null);
        } catch (ChartUploadException $exception) {
            return back()
                ->withInput()
                ->withErrors(['title' => $exception->errors]);
        }

        return back()->with('status', 'Chart details updated successfully.');
    }

    /**
     * Update the chart's status and privacy. Owner only.
     */
    public function updateVisibility(Request $request, Chart $chart): RedirectResponse
    {
        $this->ensureOwner($request, $chart);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ChartStatus::class)],
            'is_private' => ['nullable', 'boolean'],
        ]);

        $chart->status = $validated['status'];
        $chart->is_private = $request->boolean('is_private');
        $chart->save();

        return back()->with('status', 'Chart visibility updated successfully.');
    }

    /**
     * Delete the chart and all of its files. Owner only.
     */
    public function destroy(Request $request, Chart $chart): RedirectResponse
    {
        $this->ensureOwner($request, $chart);

        $title = $chart->title;

        Storage::disk('local')->deleteDirectory("charts/{$chart->id}");
        $chart->delete();

        return redirect()
            ->route('list')
            ->with('status', "“{$title}” has been deleted.");
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
}
