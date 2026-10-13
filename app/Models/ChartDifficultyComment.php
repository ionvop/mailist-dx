<?php

namespace App\Models;

use Database\Factories\ChartDifficultyCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A comment posted on a single difficulty's page.
 *
 * Any user who can view the chart may comment; only the author may delete
 * their own comment.
 */
#[Fillable([
    'difficulty_id',
    'user_id',
    'body',
])]
class ChartDifficultyComment extends Model
{
    /** @use HasFactory<ChartDifficultyCommentFactory> */
    use HasFactory;

    /**
     * The difficulty this comment belongs to.
     *
     * @return BelongsTo<ChartDifficulty, $this>
     */
    public function difficulty(): BelongsTo
    {
        return $this->belongsTo(ChartDifficulty::class);
    }

    /**
     * The user who wrote the comment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
