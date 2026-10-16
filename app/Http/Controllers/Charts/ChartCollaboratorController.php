<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\StoreChartCollaboratorRequest;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartCollaboratorController extends Controller
{
    /**
     * Grant a user edit access to the chart. Owner only.
     */
    public function store(StoreChartCollaboratorRequest $request, Chart $chart): RedirectResponse
    {
        $this->ensureOwner($request, $chart);

        $collaborator = User::where('username', $request->validated('username'))->firstOrFail();

        if ($collaborator->id === $chart->uploader_id) {
            throw ValidationException::withMessages([
                'username' => 'You already have edit access to this chart.',
            ]);
        }

        $chart->collaborators()->syncWithoutDetaching([$collaborator->id]);

        return back()->with('status', "{$collaborator->username} now has edit access.");
    }

    /**
     * Revoke a user's edit access to the chart. Owner only.
     */
    public function destroy(Request $request, Chart $chart, User $user): RedirectResponse
    {
        $this->ensureOwner($request, $chart);

        $chart->collaborators()->detach($user->id);

        return back()->with('status', "{$user->username} no longer has edit access.");
    }

    /**
     * Remove the current user's own edit access to the chart. Collaborator only.
     */
    public function leave(Request $request, Chart $chart): RedirectResponse
    {
        $user = $request->user();

        if (! $chart->isCollaborator($user)) {
            throw new NotFoundHttpException;
        }

        $chart->collaborators()->detach($user->id);

        return redirect()
            ->route('list')
            ->with('status', "You no longer have edit access to “{$chart->title}”.");
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
