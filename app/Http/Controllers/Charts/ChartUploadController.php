<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\StoreChartRequest;
use App\Services\Charts\ChartUploadException;
use App\Services\Charts\ChartUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ChartUploadController extends Controller
{
    public function __construct(private readonly ChartUploadService $uploads) {}

    /**
     * Show the chart upload form.
     */
    public function create(): View
    {
        return view('pages.charts.upload');
    }

    /**
     * Process an uploaded chart package.
     */
    public function store(StoreChartRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $chart = $this->uploads->handle(
                uploader: $request->user(),
                package: $request->file('package'),
                art: $request->file('art'),
                track: $request->file('track'),
                pv: $request->file('pv'),
                attributes: [
                    'description' => $validated['description'] ?? null,
                    'is_private' => $request->boolean('is_private'),
                ],
            );
        } catch (ChartUploadException $exception) {
            return back()
                ->withInput()
                ->withErrors(['package' => $exception->errors]);
        }

        return redirect()
            ->route('charts.show', $chart)
            ->with('status', 'Chart uploaded successfully.');
    }
}
