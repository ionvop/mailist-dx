<?php

namespace App\Support;

/**
 * Placeholder representation of the signed-in user.
 *
 * The shell has no authentication yet, so this stands in for the eventual
 * `App\Models\User` until real auth is implemented.
 */
class DevUser
{
    public function __construct(
        public string $username,
        public string $email,
        public ?string $avatarUrl = null,
    ) {}

    /**
     * Initial used as the avatar fallback when no avatar image is set.
     */
    public function initial(): string
    {
        return strtoupper(substr($this->username, 0, 1));
    }
}
