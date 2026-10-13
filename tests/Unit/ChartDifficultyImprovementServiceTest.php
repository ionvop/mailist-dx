<?php

use App\Services\Charts\ChartDifficultyImprovementService;

test('it extracts an inclusive line range', function () {
    $service = new ChartDifficultyImprovementService;
    $code = "1,2,3,\n4,5,6,\n7,8,9,";

    expect($service->extractSnippet($code, 2, 3))->toBe("4,5,6,\n7,8,9,");
});

test('it extracts a single line when no end is given', function () {
    $service = new ChartDifficultyImprovementService;
    $code = "1,2,3,\n4,5,6,\n7,8,9,";

    expect($service->extractSnippet($code, 2))->toBe('4,5,6,');
});

test('it returns null when no start line is given', function () {
    $service = new ChartDifficultyImprovementService;

    expect($service->extractSnippet("1,2,3,\n4,5,6,", null))->toBeNull();
});

test('it clamps an out-of-range selection to the code bounds', function () {
    $service = new ChartDifficultyImprovementService;
    $code = "1,2,3,\n4,5,6,";

    expect($service->extractSnippet($code, 1, 99))->toBe("1,2,3,\n4,5,6,")
        ->and($service->extractSnippet($code, 99, 99))->toBe('4,5,6,');
});

test('it normalises CRLF line endings', function () {
    $service = new ChartDifficultyImprovementService;
    $code = "1,2,3,\r\n4,5,6,\r\n7,8,9,";

    expect($service->extractSnippet($code, 2, 3))->toBe("4,5,6,\n7,8,9,");
});
