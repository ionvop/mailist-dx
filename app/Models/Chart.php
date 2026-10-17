<?php

namespace App\Models;

use App\Enums\ChartStatus;
use Database\Factories\ChartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

#[Fillable([
    'uploader_id',
    'title',
    'title_alias',
    'artist',
    'artist_alias',
    'artist_link',
    'illustrator',
    'illustrator_link',
    'note_designer',
    'audio_bpm',
    'audio_offset',
    'info',
    'description',
    'tags',
    'status',
    'is_private',
    'art_path',
    'track_path',
    'pv_path',
    'maidata_path',
    'download_path',
])]
class Chart extends Model
{
    /** @use HasFactory<ChartFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'status' => ChartStatus::class,
            'is_private' => 'boolean',
        ];
    }

    /**
     * The user who uploaded the chart.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * The difficulties (charts) contained in this level.
     *
     * @return HasMany<ChartDifficulty, $this>
     */
    public function difficulties(): HasMany
    {
        return $this->hasMany(ChartDifficulty::class)->orderBy('chart_index');
    }

    /**
     * The comments posted on this chart, newest first.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest('id');
    }

    /**
     * The users granted edit access to this chart.
     *
     * @return BelongsToMany<User, $this>
     */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chart_collaborators')->withTimestamps();
    }

    /**
     * The users who have liked this chart.
     *
     * @return BelongsToMany<User, $this>
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chart_likes')->withTimestamps();
    }

    /**
     * Every code revision recorded across the chart's difficulties.
     *
     * @return HasManyThrough<ChartDifficultyRevision, ChartDifficulty, $this>
     */
    public function revisions(): HasManyThrough
    {
        return $this->hasManyThrough(
            ChartDifficultyRevision::class,
            ChartDifficulty::class,
            'chart_id',
            'difficulty_id',
            'id',
            'id',
        );
    }

    /**
     * The most recent revision across all of the chart's difficulties, or null
     * when the chart has no revisions yet.
     *
     * Requires the `difficulties.latestRevision` relation to be eager loaded.
     */
    public function mostRecentRevision(): ?ChartDifficultyRevision
    {
        return $this->difficulties
            ->map(fn (ChartDifficulty $difficulty) => $difficulty->latestRevision)
            ->filter()
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * The chart description rendered from Markdown, or null when none is set.
     *
     * Raw HTML is stripped and unsafe links are disallowed to keep the
     * rendered output safe to display without escaping.
     */
    protected function descriptionHtml(): Attribute
    {
        return Attribute::get(
            fn (): ?string => filled($this->description)
                ? Str::markdown($this->description, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ])
                : null,
        );
    }

    /**
     * Whether the given user is allowed to view this chart.
     */
    public function isVisibleTo(?User $user): bool
    {
        if (! $this->is_private) {
            return true;
        }

        return $this->canEdit($user);
    }

    /**
     * Whether the given user owns (and may manage) this chart.
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $user->id === $this->uploader_id;
    }

    /**
     * Whether the given user has been granted edit access to this chart.
     */
    public function isCollaborator(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->collaborators()->whereKey($user->id)->exists();
    }

    /**
     * Whether the given user has liked this chart.
     */
    public function isLikedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->likes()->whereKey($user->id)->exists();
    }

    /**
     * Whether the given user may edit this chart, either as its owner or as
     * a collaborator with edit access.
     */
    public function canEdit(?User $user): bool
    {
        return $this->isOwnedBy($user) || $this->isCollaborator($user);
    }

    /**
     * Whether the chart has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === ChartStatus::Approved;
    }

    /**
     * Whether the given user may approve or revert this chart. Approvers may
     * only act on charts they do not own, so they cannot approve their own.
     */
    public function canApprove(?User $user): bool
    {
        return $user !== null && $user->isApprover() && ! $this->isOwnedBy($user);
    }
}
