<?php

namespace App\Models;

use Database\Factories\ChartDifficultyRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable snapshot of a difficulty's simai code at a point in time.
 *
 * The original (uploaded) state is recorded when the chart is uploaded and
 * attributed to the uploader. A revision is then recorded every time the code
 * changes. Restoring an old revision does not rewrite history — it appends a
 * new revision.
 */
#[Fillable([
    'difficulty_id',
    'user_id',
    'code',
    'is_original',
])]
class ChartDifficultyRevision extends Model
{
    /** @use HasFactory<ChartDifficultyRevisionFactory> */
    use HasFactory;

    /**
     * Revisions only ever have a creation timestamp.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_original' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The difficulty this revision belongs to.
     *
     * @return BelongsTo<ChartDifficulty, $this>
     */
    public function difficulty(): BelongsTo
    {
        return $this->belongsTo(ChartDifficulty::class);
    }

    /**
     * The user who saved this revision, or the uploader for the original
     * version. Null only when that user has since been deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
