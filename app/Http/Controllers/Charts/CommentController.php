<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\StoreCommentRequest;
use App\Models\Chart;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CommentController extends Controller
{
    /**
     * Store a new comment on the chart.
     */
    public function store(StoreCommentRequest $request, Chart $chart): RedirectResponse
    {
        if (! $chart->isVisibleTo($request->user())) {
            throw new NotFoundHttpException;
        }

        $chart->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()
            ->route('charts.show', $chart)
            ->withFragment('comments')
            ->with('status', 'Comment posted.');
    }

    /**
     * Delete a comment from the chart.
     */
    public function destroy(Request $request, Chart $chart, Comment $comment): RedirectResponse
    {
        if ($comment->chart_id !== $chart->id) {
            throw new NotFoundHttpException;
        }

        abort_unless($comment->isDeletableBy($request->user(), $chart->uploader_id), 403);

        $comment->delete();

        return redirect()
            ->route('charts.show', $chart)
            ->withFragment('comments')
            ->with('status', 'Comment deleted.');
    }
}
