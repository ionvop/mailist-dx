<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'chart_id',
    'user_id',
    'body',
])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * The chart this comment belongs to.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }

    /**
     * The user who wrote this comment.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Whether the given user is allowed to delete this comment.
     *
     * The comment author and the chart owner may delete a comment. The chart
     * owner id may be passed in to avoid loading the chart relation.
     */
    public function isDeletableBy(?User $user, ?int $chartOwnerId = null): bool
    {
        if ($user === null) {
            return false;
        }

        $ownerId = $chartOwnerId ?? $this->chart->uploader_id;

        return $user->id === $this->user_id || $user->id === $ownerId;
    }
}
