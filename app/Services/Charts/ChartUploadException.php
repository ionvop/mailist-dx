<?php

namespace App\Services\Charts;

use RuntimeException;

/**
 * Thrown when a chart package cannot be processed.
 */
class ChartUploadException extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct($errors[0] ?? 'The chart package could not be processed.');
    }
}
