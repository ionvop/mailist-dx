<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\StoreCommentRequest;
use App\Models\Chart;
use App\Models\Comment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartCommentController extends Controller
{
    /**
     * Show a chart's comments.
     */
    public function index(Request $request, Chart $chart): View
    {
        $this->ensureVisible($request, $chart);

        $chart->load('uploader');

        return view('pages.charts.comments', [
            'chart' => $chart,
            'comments' => $chart->comments()->with('user')->get(),
        ]);
    }

    /**
     * Post a comment on a chart. Any user who can view the chart may comment.
     */
    public function store(StoreCommentRequest $request, Chart $chart): RedirectResponse
    {
        $this->ensureVisible($request, $chart);

        $validated = $request->validated();

        $chart->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return back()->with('status', 'Your comment has been posted.');
    }

    /**
     * Delete a comment. Only the author may delete their own comment.
     */
    public function destroy(Request $request, Chart $chart, Comment $comment): RedirectResponse
    {
        $this->ensureVisible($request, $chart);

        abort_unless($comment->chart_id === $chart->id, 404);
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return back()->with('status', 'Your comment has been deleted.');
    }

    /**
     * Abort with a 404 when the chart is not visible to the current user.
     */
    private function ensureVisible(Request $request, Chart $chart): void
    {
        if (! $chart->isVisibleTo($request->user())) {
            throw new NotFoundHttpException;
        }
    }
}
