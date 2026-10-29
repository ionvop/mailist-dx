<?php

namespace App\Models;

use App\Services\Maidata\MaidataLineEndings;
use Database\Factories\ChartDifficultyRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 *
 * The `note` is the one field that may be changed after creation: the author
 * can annotate their own revision with a short summary, like a git commit
 * message, at any time.
 */
#[Fillable([
    'difficulty_id',
    'user_id',
    'revision_number',
    'code',
    'note',
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
            'revision_number' => 'integer',
            'is_original' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Normalise the snapshotted code to LF line endings on write.
     *
     * Revisions must match the difficulty's stored code byte-for-byte, so the
     * same LF-only invariant is enforced here.
     */
    protected function code(): Attribute
    {
        return Attribute::set(
            fn (string $value): string => MaidataLineEndings::normalize($value),
        );
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
