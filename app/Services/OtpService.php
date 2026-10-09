<?php

namespace App\Services;

use App\Models\EmailCode;

/**
 * Issues and verifies the one-time codes used for passwordless email login.
 *
 * Codes are stored hashed, expire after a short window, and are consumed on
 * first successful verification.
 */
class OtpService
{
    /**
     * How long a generated code stays valid, in minutes.
     */
    public const TTL_MINUTES = 5;

    /**
     * Generate a fresh code for the email, replacing any previous one.
     */
    public function generate(string $email): string
    {
        $code = (string) random_int(100000, 999999);

        EmailCode::query()->where('email', $email)->delete();

        EmailCode::create([
            'email' => $email,
            'code_hash' => bcrypt($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $code;
    }

    /**
     * Verify a code for the email, consuming it on success.
     */
    public function verify(string $email, string $code): bool
    {
        $record = EmailCode::query()->where('email', $email)->latest()->first();

        if (! $record || $record->isExpired()) {
            return false;
        }

        if (! password_verify($code, $record->code_hash)) {
            return false;
        }

        $record->delete();

        return true;
    }
}
