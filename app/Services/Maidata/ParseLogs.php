<?php

namespace App\Services\Maidata;

/**
 * Collects diagnostics produced while parsing a maidata file.
 */
class ParseLogs
{
    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  list<string>  $infos
     */
    public function __construct(
        public array $errors = [],
        public array $warnings = [],
        public array $infos = [],
    ) {}

    public function error(string $message): void
    {
        $this->errors[] = $message;
    }

    public function warning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function info(string $message): void
    {
        $this->infos[] = $message;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
