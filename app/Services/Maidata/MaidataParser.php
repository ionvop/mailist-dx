<?php

namespace App\Services\Maidata;

use App\Enums\ChartDifficultyName;

/**
 * Parses a maimai `maidata.txt` file into level metadata and difficulties.
 *
 * This is a pure-PHP port of the reference parser. It reads the `&key=value`
 * pairs used by the format and does not compute note counts or fingerprints.
 */
class MaidataParser
{
    /**
     * Maximum length for a standard text field.
     */
    private const MAX_TEXT_LENGTH = 500;

    /**
     * Maximum length for a long text field.
     */
    private const MAX_LONG_TEXT_LENGTH = 5000;

    /**
     * Chart indices supported by the maidata format.
     */
    private const CHART_INDICES = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * Parse the given maidata contents.
     */
    public function parse(string $contents): ParsedMaidata
    {
        $logs = new ParseLogs;
        $pairs = $this->toKeyValuePairs($contents);

        // Custom media paths are not supported; media is resolved from the package.
        foreach (['track', 'bg', 'pv'] as $unsupported) {
            if (array_key_exists($unsupported, $pairs)) {
                $logs->error("Custom media path (&{$unsupported}=) is not supported.");
            }
        }

        $title = $this->extract($pairs, 'title', 'Song title', self::MAX_TEXT_LENGTH, $logs->errors) ?? '?';
        $artist = $this->extract($pairs, 'artist', 'Artist name', self::MAX_TEXT_LENGTH, $logs->errors) ?? '?';

        $artistLink = $this->extract($pairs, 'artist_link', 'Link to artist bio', self::MAX_TEXT_LENGTH, $logs->infos);
        if ($artistLink !== null && ! $this->isUrl($artistLink)) {
            $logs->error('&artist_link is not a valid URL.');
        }

        $illustratorLink = $this->extract($pairs, 'illus_link', 'Link to illustrator bio', self::MAX_TEXT_LENGTH, $logs->infos);
        if ($illustratorLink !== null && ! $this->isUrl($illustratorLink)) {
            $logs->error('&illus_link is not a valid URL.');
        }

        $tags = [];
        $tagsString = $this->extract($pairs, 'tags', 'Chart tags', self::MAX_TEXT_LENGTH, $logs->infos);
        if ($tagsString !== null) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $tagsString))));
        }

        $charts = $this->parseCharts($pairs, $logs);

        if ($charts === []) {
            $logs->error('Level has no charts (&inote_#).');
        }

        return new ParsedMaidata(
            title: $title,
            titleAlias: $this->extract($pairs, 'title_alias', 'Alternative song title(s)', self::MAX_TEXT_LENGTH, $logs->infos),
            artist: $artist,
            artistAlias: $this->extract($pairs, 'artist_alias', 'Alternative artist name(s)', self::MAX_TEXT_LENGTH, $logs->infos),
            artistLink: $artistLink,
            illustrator: $this->extract($pairs, 'illus', 'Illustrator name', self::MAX_TEXT_LENGTH, $logs->infos),
            illustratorLink: $illustratorLink,
            audioBpm: $this->extract($pairs, 'wholebpm', 'BPM', 50, $logs->infos),
            info: $this->extract($pairs, 'freemsg', 'Level info', self::MAX_LONG_TEXT_LENGTH, $logs->warnings),
            tags: $tags,
            previewStart: $this->toFloat($this->extract($pairs, 'preview_start', 'Audio preview start', 50, $logs->infos)),
            previewEnd: $this->toFloat($this->extract($pairs, 'preview_end', 'Audio preview end', 50, $logs->infos)),
            charts: $charts,
            logs: $logs,
        );
    }

    /**
     * Parse each defined chart from the key/value pairs.
     *
     * @param  array<string, string>  $pairs
     * @return list<ParsedChart>
     */
    private function parseCharts(array $pairs, ParseLogs $logs): array
    {
        $charts = [];
        $levelCharter = $pairs['des'] ?? null;

        foreach (self::CHART_INDICES as $index) {
            $code = $pairs["inote_{$index}"] ?? null;

            if ($code === null || trim($code) === '') {
                continue;
            }

            $logs->info("Found chart {$index} (&inote_{$index}).");

            $difficulty = ChartDifficultyName::fromChartIndex($index);
            $difficultyAlias = null;

            $alias = $this->extract($pairs, "alias_{$index}", "Chart {$index} alias", 50, $logs->infos);
            if ($alias !== null) {
                $standard = ChartDifficultyName::tryFrom(strtolower($alias));

                if ($standard !== null) {
                    $difficulty = $standard;
                } else {
                    $logs->info("Chart {$index} uses a custom difficulty name.");
                    $difficulty = ChartDifficultyName::Custom;
                    $difficultyAlias = $alias;
                }
            }

            $color = $this->extract($pairs, "color_{$index}", "Chart {$index} custom color", 10, $logs->warnings);
            if ($color !== null && ! preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
                $logs->error("Chart {$index} custom color (&color_{$index}=) is not in hex format.");
                $color = null;
            }

            $charts[] = new ParsedChart(
                chartIndex: $index,
                difficulty: $difficulty,
                difficultyAlias: $difficultyAlias,
                difficultyColor: $color,
                difficultyValue: $this->extract($pairs, "lv_{$index}", "Chart {$index} difficulty", 50, $logs->errors),
                charter: $this->resolveCharter($pairs, $index, $levelCharter, $logs),
                info: $this->extract($pairs, "smsg_{$index}", "Chart {$index} info", self::MAX_LONG_TEXT_LENGTH, $logs->warnings),
                code: trim($code),
            );
        }

        return $charts;
    }

    /**
     * Resolve the charter name for a chart, preferring the chart-specific value.
     *
     * @param  array<string, string>  $pairs
     */
    private function resolveCharter(array $pairs, string $index, ?string $levelCharter, ParseLogs $logs): ?string
    {
        $chartCharter = $this->extract($pairs, "des_{$index}", "Chart {$index} charter", self::MAX_TEXT_LENGTH, $logs->infos);

        if ($chartCharter !== null) {
            return $chartCharter;
        }

        if ($levelCharter !== null && $levelCharter !== '') {
            return $levelCharter;
        }

        $logs->warning("Charter name for chart {$index} (&des_{$index}=, &des=) not found.");

        return null;
    }

    /**
     * Extract a field, logging when it is missing, empty, or too long.
     *
     * @param  array<string, string>  $pairs
     * @param  list<string>  $logger
     */
    private function extract(array $pairs, string $key, string $fieldName, int $maxLength, array &$logger): ?string
    {
        if (! array_key_exists($key, $pairs)) {
            $logger[] = "{$fieldName} (&{$key}=) not found.";

            return null;
        }

        $value = $pairs[$key];

        if ($value === '') {
            $logger[] = "{$fieldName} (&{$key}=) is empty.";

            return null;
        }

        if (mb_strlen($value) > $maxLength) {
            $logger[] = "{$fieldName} (&{$key}=) is too long (".mb_strlen($value)." > {$maxLength} characters).";

            return null;
        }

        return $value;
    }

    /**
     * Convert the maidata contents into `&key=value` pairs.
     *
     * Values may span multiple lines and continue until the next `&key=` line.
     *
     * @return array<string, string>
     */
    private function toKeyValuePairs(string $contents): array
    {
        $contents = $this->stripBom($contents);
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);

        $pairs = [];
        $currentKey = null;
        $buffer = [];

        $flush = function () use (&$pairs, &$currentKey, &$buffer): void {
            if ($currentKey !== null) {
                $pairs[$currentKey] = trim(implode("\n", $buffer));
            }

            $currentKey = null;
            $buffer = [];
        };

        foreach (explode("\n", $contents) as $line) {
            if (preg_match('/^&([A-Za-z0-9_]+)=(.*)$/s', $line, $matches) === 1) {
                $flush();
                $currentKey = $matches[1];
                $buffer = [$matches[2]];

                continue;
            }

            if ($currentKey !== null) {
                $buffer[] = $line;
            }
        }

        $flush();

        return $pairs;
    }

    /**
     * Remove a leading UTF-8 byte order mark.
     */
    private function stripBom(string $contents): string
    {
        return str_starts_with($contents, "\xEF\xBB\xBF") ? substr($contents, 3) : $contents;
    }

    /**
     * Parse a float, returning null when the value is not numeric.
     */
    private function toFloat(?string $value): ?float
    {
        if ($value === null || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Determine whether the value is a valid URL.
     */
    private function isUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
