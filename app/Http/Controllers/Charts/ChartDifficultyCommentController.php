<?php

namespace App\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charts\StoreDifficultyCommentRequest;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyComment;
use App\Notifications\ChartDifficultyCommentCreated;
use App\Notifications\ContentRemoved;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChartDifficultyCommentController extends Controller
{
    /**
     * Post a comment on a difficulty. Any user who can view the chart may
     * comment.
     */
    public function store(StoreDifficultyCommentRequest $request, Chart $chart, ChartDifficulty $difficulty): RedirectResponse
    {
        $this->ensureVisible($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);

        $comment = $difficulty->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        if (! $chart->isOwnedBy($request->user())) {
            $chart->uploader->notify(new ChartDifficultyCommentCreated($comment));
        }

        return redirect()
            ->route('charts.difficulties.show', [$chart, $difficulty, 'comments'])
            ->with('status', 'Your comment has been posted.');
    }

    /**
     * Delete a comment. The author may delete their own comment, and
     * moderators may delete any comment.
     */
    public function destroy(Request $request, Chart $chart, ChartDifficulty $difficulty, ChartDifficultyComment $comment): RedirectResponse
    {
        $this->ensureVisible($request, $chart);
        $this->ensureBelongsToChart($chart, $difficulty);
        $this->ensureCommentBelongsToDifficulty($difficulty, $comment);

        $isModeration = $comment->user_id !== $request->user()->id;

        if ($isModeration) {
            abort_unless($request->user()->isModerator(), 403);
        }

        $comment->delete();

        if ($isModeration) {
            $comment->user->notify(new ContentRemoved(
                'Comment removed',
                "Your comment on {$difficulty->displayName()} of \"{$chart->title}\" was removed by a moderator.",
                route('charts.difficulties.show', [$chart, $difficulty, 'comments']),
            ));

            return back()->with('status', 'The comment has been removed.');
        }

        return back()->with('status', 'Your comment has been deleted.');
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
     * Abort with a 404 when the difficulty does not belong to the chart.
     */
    private function ensureBelongsToChart(Chart $chart, ChartDifficulty $difficulty): void
    {
        if ($difficulty->chart_id !== $chart->id) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Abort with a 404 when the comment does not belong to the difficulty.
     */
    private function ensureCommentBelongsToDifficulty(ChartDifficulty $difficulty, ChartDifficultyComment $comment): void
    {
        if ($comment->difficulty_id !== $difficulty->id) {
            throw new NotFoundHttpException;
        }
    }
}
