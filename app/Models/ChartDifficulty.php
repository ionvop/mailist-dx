<?php

namespace App\Models;

use App\Enums\ChartDifficultyName;
use Database\Factories\ChartDifficultyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'chart_id',
    'chart_index',
    'difficulty',
    'difficulty_alias',
    'difficulty_color',
    'difficulty_value',
    'charter',
    'info',
    'code',
    'fingerprint',
    'extra',
    'ranked_rating',
])]
class ChartDifficulty extends Model
{
    /** @use HasFactory<ChartDifficultyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'difficulty' => ChartDifficultyName::class,
            'extra' => 'array',
            'ranked_rating' => 'integer',
        ];
    }

    /**
     * The level this difficulty belongs to.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }

    /**
     * The code snapshots recorded for this difficulty, newest first.
     *
     * @return HasMany<ChartDifficultyRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(ChartDifficultyRevision::class, 'difficulty_id')->latest('id');
    }

    /**
     * The most recent revision recorded for this difficulty.
     *
     * @return HasOne<ChartDifficultyRevision, $this>
     */
    public function latestRevision(): HasOne
    {
        return $this->hasOne(ChartDifficultyRevision::class, 'difficulty_id')->latestOfMany();
    }

    /**
     * The improvement suggestions posted for this difficulty, newest first.
     *
     * @return HasMany<ChartDifficultyImprovement, $this>
     */
    public function improvements(): HasMany
    {
        return $this->hasMany(ChartDifficultyImprovement::class, 'difficulty_id')->latest('id');
    }

    /**
     * The comments posted on this difficulty, newest first.
     *
     * @return HasMany<ChartDifficultyComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ChartDifficultyComment::class, 'difficulty_id')->latest('id');
    }

    /**
     * The display name for the difficulty, preferring a custom alias.
     */
    public function displayName(): string
    {
        return $this->difficulty_alias ?: $this->difficulty->label();
    }
}
