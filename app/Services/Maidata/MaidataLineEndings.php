<?php

namespace App\Services\Maidata;

/**
 * Normalises the line endings of maidata contents.
 *
 * The application stores maidata with LF line endings only, regardless of the
 * platform the package was authored on. A leading UTF-8 byte order mark is
 * left untouched; only carriage returns are rewritten.
 *
 * The methods are static because the normaliser is a pure string helper used
 * from services, Eloquent mutators, and tests alike.
 */
class MaidataLineEndings
{
    /**
     * Rewrite CRLF and lone CR line endings to LF.
     */
    public static function normalize(string $contents): string
    {
        return str_replace(["\r\n", "\r"], "\n", $contents);
    }

    /**
     * Determine whether the contents contain any carriage returns.
     */
    public static function hasCarriageReturns(string $contents): bool
    {
        return str_contains($contents, "\r");
    }
}
