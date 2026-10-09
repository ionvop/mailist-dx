<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'email', 'avatar_path'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Public URL for the user's avatar, or null when none is set.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->avatar_path ? asset($this->avatar_path) : null,
        );
    }

    /**
     * Initial used as the avatar fallback when no avatar image is set.
     */
    public function initial(): string
    {
        return strtoupper(substr($this->username, 0, 1));
    }
}
