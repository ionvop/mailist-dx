<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * Development-only stand-in for the auth guard.
 *
 * Lets the shell render both the guest and the signed-in chrome without any
 * real authentication. It is inert outside the local environment.
 */
class DevAuth
{
    public const SESSION_KEY = 'dev.auth';

    /**
     * Environments where the placeholder auth state is available.
     *
     * @var list<string>
     */
    public const ENVIRONMENTS = ['local', 'testing'];

    public static function enabled(): bool
    {
        return app()->environment(self::ENVIRONMENTS);
    }

    public static function check(): bool
    {
        return self::enabled() && Session::get(self::SESSION_KEY, false) === true;
    }

    public static function user(): ?DevUser
    {
        if (! self::check()) {
            return null;
        }

        return new DevUser(
            username: 'devuser',
            email: 'dev@mailist.test',
        );
    }

    public static function toggle(): bool
    {
        $state = ! self::check();

        Session::put(self::SESSION_KEY, $state);

        return $state;
    }
}
