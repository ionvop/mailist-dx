<?php

namespace App\Services\Maidata;

/**
 * Rewrites editable `&key=value` headers in a maidata file.
 *
 * Only the keys passed in are touched: existing lines are replaced in place,
 * keys that are new are appended to the header block, and keys whose value is
 * null or empty are removed. Chart data (`&inote_#`), per-difficulty keys, and
 * any other headers are left byte-for-byte untouched. The original UTF-8 BOM
 * and line-ending style are preserved.
 */
class MaidataSerializer
{
    /**
     * Rewrite the given header values in the maidata contents.
     *
     * @param  array<string, string|null>  $values  Map of maidata key to its new value.
     *                                              A null or empty value removes the key.
     */
    public function replaceHeaderValues(string $contents, array $values): string
    {
        $hasBom = str_starts_with($contents, "\xEF\xBB\xBF");

        if ($hasBom) {
            $contents = substr($contents, 3);
        }

        $eol = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $contents));

        $output = [];
        $insertAt = null;
        $handled = [];
        $skipContinuation = false;

        foreach ($lines as $line) {
            if ($insertAt === null && preg_match('/^&inote_[0-9]+=/', $line) === 1) {
                $insertAt = count($output);
            }

            if (preg_match('/^&([A-Za-z0-9_]+)=(.*)$/s', $line, $matches) === 1) {
                $key = $matches[1];
                $skipContinuation = false;

                if (array_key_exists($key, $values)) {
                    $handled[$key] = true;

                    $value = $values[$key];

                    if ($value !== null && $value !== '') {
                        $output[] = "&{$key}=".$this->sanitize($value);
                    }

                    // Drop any continuation lines belonging to this (possibly removed) key.
                    $skipContinuation = true;

                    continue;
                }

                $output[] = $line;

                continue;
            }

            if ($skipContinuation) {
                continue;
            }

            $output[] = $line;
        }

        // Append values for keys that did not exist in the file yet.
        $appended = [];

        foreach ($values as $key => $value) {
            if (isset($handled[$key]) || $value === null || $value === '') {
                continue;
            }

            $appended[] = "&{$key}=".$this->sanitize((string) $value);
        }

        if ($appended !== []) {
            $insertAt ??= count($output);
            array_splice($output, $insertAt, 0, $appended);
        }

        $result = implode($eol, $output);

        return $hasBom ? "\xEF\xBB\xBF".$result : $result;
    }

    /**
     * Replace the `&inote_#` chart body for the given index.
     *
     * Unlike {@see replaceHeaderValues()}, this supports multi-line chart code,
     * which is written verbatim (normalised to the file's line endings). An
     * existing body is replaced in place together with any continuation lines,
     * and a missing index is appended to the header block. The original UTF-8
     * BOM and line-ending style are preserved.
     */
    public function replaceChartCode(string $contents, string $index, string $code): string
    {
        $hasBom = str_starts_with($contents, "\xEF\xBB\xBF");

        if ($hasBom) {
            $contents = substr($contents, 3);
        }

        $eol = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $contents));

        $body = implode($eol, explode("\n", str_replace(["\r\n", "\r"], "\n", $code)));

        $pattern = '/^&inote_'.preg_quote($index, '/').'=(.*)$/s';

        $output = [];
        $insertAt = null;
        $replaced = false;
        $skipContinuation = false;

        foreach ($lines as $line) {
            if ($insertAt === null && preg_match('/^&inote_[0-9]+=/', $line) === 1) {
                $insertAt = count($output);
            }

            if ($skipContinuation) {
                if (preg_match('/^&[A-Za-z0-9_]+=/', $line) === 1) {
                    $skipContinuation = false;
                } else {
                    continue;
                }
            }

            if (preg_match($pattern, $line) === 1) {
                $output[] = "&inote_{$index}={$body}";
                $replaced = true;
                $skipContinuation = true;

                continue;
            }

            $output[] = $line;
        }

        if (! $replaced) {
            $insertAt ??= count($output);
            array_splice($output, $insertAt, 0, ["&inote_{$index}={$body}"]);
        }

        $result = implode($eol, $output);

        return $hasBom ? "\xEF\xBB\xBF".$result : $result;
    }

    /**
     * Remove every per-difficulty header for the given chart index.
     *
     * Drops the `&inote_#` body (including its continuation lines) together with
     * the matching `&lv_#`, `&des_#`, `&alias_#`, `&color_#`, and `&smsg_#`
     * headers, so the difficulty no longer appears in the file. Headers for
     * other indices and the level-wide keys are left untouched. The original
     * UTF-8 BOM and line-ending style are preserved.
     */
    public function removeChart(string $contents, string $index): string
    {
        $hasBom = str_starts_with($contents, "\xEF\xBB\xBF");

        if ($hasBom) {
            $contents = substr($contents, 3);
        }

        $eol = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $contents));

        $pattern = '/^&[A-Za-z0-9]+_'.preg_quote($index, '/').'=/';

        $output = [];
        $skipContinuation = false;

        foreach ($lines as $line) {
            if ($skipContinuation) {
                if (preg_match('/^&[A-Za-z0-9_]+=/', $line) === 1) {
                    $skipContinuation = false;
                } else {
                    continue;
                }
            }

            if (preg_match($pattern, $line) === 1) {
                $skipContinuation = true;

                continue;
            }

            $output[] = $line;
        }

        $result = implode($eol, $output);

        return $hasBom ? "\xEF\xBB\xBF".$result : $result;
    }

    /**
     * Normalise a header value so it cannot break out onto a new line.
     */
    private function sanitize(string $value): string
    {
        return str_replace(["\r\n", "\r", "\n"], ' ', $value);
    }
}
