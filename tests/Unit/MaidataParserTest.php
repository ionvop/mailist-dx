<?php

use App\Enums\ChartDifficultyName;
use App\Services\Maidata\MaidataParser;

test('it parses level metadata and difficulties', function () {
    $parsed = (new MaidataParser)->parse(sampleMaidata());

    expect($parsed->title)->toBe('Test Song')
        ->and($parsed->artist)->toBe('Test Artist')
        ->and($parsed->noteDesigner)->toBe('Test Charter')
        ->and($parsed->audioBpm)->toBe('180')
        ->and($parsed->tags)->toBe(['tag1', 'tag2'])
        ->and($parsed->charts)->toHaveCount(2)
        ->and($parsed->logs->hasErrors())->toBeFalse();
});

test('it maps chart indices to difficulties and reads values', function () {
    $parsed = (new MaidataParser)->parse(sampleMaidata());

    $expert = $parsed->charts[0];
    $master = $parsed->charts[1];

    expect($expert->chartIndex)->toBe('4')
        ->and($expert->difficulty)->toBe(ChartDifficultyName::Expert)
        ->and($expert->difficultyValue)->toBe('12')
        ->and($expert->charter)->toBe('Test Charter')
        ->and($master->difficulty)->toBe(ChartDifficultyName::Master)
        ->and($master->difficultyValue)->toBe('14+')
        ->and($master->charter)->toBe('Master Charter');
});

test('it uses a custom difficulty when the alias is non-standard', function () {
    $maidata = "&title=Song\n&artist=Artist\n&inote_1=1,2,3,\n&lv_1=1\n&alias_1=Insane\n";

    $parsed = (new MaidataParser)->parse($maidata);

    expect($parsed->charts[0]->difficulty)->toBe(ChartDifficultyName::Custom)
        ->and($parsed->charts[0]->difficultyAlias)->toBe('Insane');
});

test('it rejects custom media paths', function () {
    $maidata = "&title=Song\n&artist=Artist\n&track=custom.mp3\n&inote_1=1,2,3,\n&lv_1=1\n";

    $parsed = (new MaidataParser)->parse($maidata);

    expect($parsed->logs->hasErrors())->toBeTrue()
        ->and($parsed->logs->errors)->toContain('Custom media path (&track=) is not supported.');
});

test('it reports an error when there are no charts', function () {
    $parsed = (new MaidataParser)->parse("&title=Song\n&artist=Artist\n");

    expect($parsed->logs->hasErrors())->toBeTrue();
});

test('it strips a byte order mark', function () {
    $parsed = (new MaidataParser)->parse("\xEF\xBB\xBF".sampleMaidata());

    expect($parsed->title)->toBe('Test Song');
});
