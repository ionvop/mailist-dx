<?php

namespace App\Models;

use App\Enums\ImprovementStatus;
use Database\Factories\ChartDifficultyImprovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user-suggested improvement for a difficulty's simai code.
 *
 * A suggestion may optionally reference a line range of the code (with the
 * snippet captured at posting time) for context. Editors (the chart owner or a
 * collaborator) can resolve it with a remark or reject it with a reason, and
 * may optionally link the revision in which it was resolved.
 */
#[Fillable([
    'difficulty_id',
    'user_id',
    'body',
    'line_start',
    'line_end',
    'snippet',
    'status',
    'resolution_note',
    'resolved_by',
    'resolved_at',
    'revision_id',
])]
class ChartDifficultyImprovement extends Model
{
    /** @use HasFactory<ChartDifficultyImprovementFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImprovementStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * The difficulty this suggestion belongs to.
     *
     * @return BelongsTo<ChartDifficulty, $this>
     */
    public function difficulty(): BelongsTo
    {
        return $this->belongsTo(ChartDifficulty::class);
    }

    /**
     * The user who suggested the improvement.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The editor who resolved or rejected the suggestion.
     *
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * The revision in which the suggestion was resolved, if linked.
     *
     * @return BelongsTo<ChartDifficultyRevision, $this>
     */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(ChartDifficultyRevision::class, 'revision_id');
    }

    /**
     * Whether the suggestion has a code snippet attached.
     */
    public function hasSnippet(): bool
    {
        return $this->line_start !== null && $this->snippet !== null;
    }

    /**
     * Human-readable label for the referenced line range, e.g. "L 45-47".
     */
    public function lineLabel(): ?string
    {
        if ($this->line_start === null) {
            return null;
        }

        if ($this->line_end === null || $this->line_end === $this->line_start) {
            return 'L '.$this->line_start;
        }

        return 'L '.$this->line_start.'-'.$this->line_end;
    }
}
