<?php

namespace App\Models;

use App\Enums\ChartStatus;
use Database\Factories\ChartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

        return $user !== null && $user->id === $this->uploader_id;
    }
}
