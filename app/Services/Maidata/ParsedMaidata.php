<?php

namespace App\Services\Maidata;

/**
 * The result of parsing a maidata file: level metadata plus its difficulties.
 */
class ParsedMaidata
{
    /**
     * @param  list<string>  $tags
     * @param  list<ParsedChart>  $charts
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $titleAlias,
        public readonly string $artist,
        public readonly ?string $artistAlias,
        public readonly ?string $artistLink,
        public readonly ?string $illustrator,
        public readonly ?string $illustratorLink,
        public readonly ?string $noteDesigner,
        public readonly ?string $audioBpm,
        public readonly ?float $audioOffset,
        public readonly ?string $info,
        public readonly array $tags,
        public readonly ?float $previewStart,
        public readonly ?float $previewEnd,
        public readonly array $charts,
        public readonly ParseLogs $logs,
    ) {}
}
