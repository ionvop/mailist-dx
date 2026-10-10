<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['username', 'email', 'avatar_path', 'bio', 'username_changed_at'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * How long a user must wait before changing their username again.
     */
    public const USERNAME_CHANGE_COOLDOWN_MINUTES = 5;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'username_changed_at' => 'datetime',
        ];
    }

    /**
     * The charts uploaded by the user.
     *
     * @return HasMany<Chart, $this>
     */
    public function charts(): HasMany
    {
        return $this->hasMany(Chart::class, 'uploader_id');
    }

    /**
     * The comments written by the user.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * The charts the user has been granted edit access to.
     *
     * @return BelongsToMany<Chart, $this>
     */
    public function collaboratedCharts(): BelongsToMany
    {
        return $this->belongsToMany(Chart::class, 'chart_collaborators')->withTimestamps();
    }

    /**
     * Public URL for the user's avatar, or null when none is set.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
        );
    }

    /**
     * The user's biography rendered from Markdown, or null when none is set.
     *
     * Raw HTML is stripped and unsafe links are disallowed to keep the
     * rendered output safe to display without escaping.
     */
    protected function bioHtml(): Attribute
    {
        return Attribute::get(
            fn (): ?string => filled($this->bio)
                ? Str::markdown($this->bio, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ])
                : null,
        );
    }

    /**
     * Whether the user is allowed to change their username right now.
     */
    public function canChangeUsername(): bool
    {
        return $this->usernameChangeAvailableAt()->isPast();
    }

    /**
     * The timestamp at which the username change cooldown expires.
     */
    public function usernameChangeAvailableAt(): Carbon
    {
        if (! $this->username_changed_at) {
            return now()->subSecond();
        }

        return $this->username_changed_at->copy()->addMinutes(self::USERNAME_CHANGE_COOLDOWN_MINUTES);
    }

    /**
     * Initial used as the avatar fallback when no avatar image is set.
     */
    public function initial(): string
    {
        return strtoupper(substr($this->username, 0, 1));
    }
}
