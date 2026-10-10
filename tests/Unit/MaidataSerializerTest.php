<?php

use App\Services\Maidata\MaidataSerializer;

function sampleSerializerMaidata(): string
{
    return implode("\n", [
        '&title=Old Title',
        '&artist=Old Artist',
        '&shortid=689',
        '&des=Old Designer',
        '&inote_2=(179){1}1,2,3,',
        '&lv_2=5',
        '&inote_3=(179){1}4,5,6,',
        'E',
        '',
    ]);
}

test('it replaces an existing header value in place', function () {
    $result = (new MaidataSerializer)->replaceHeaderValues(
        sampleSerializerMaidata(),
        ['title' => 'New Title'],
    );

    expect($result)
        ->toContain('&title=New Title')
        ->not->toContain('&title=Old Title');
});

test('it leaves unknown keys and chart data untouched', function () {
    $result = (new MaidataSerializer)->replaceHeaderValues(
        sampleSerializerMaidata(),
        ['title' => 'New Title'],
    );

    expect($result)
        ->toContain('&artist=Old Artist')
        ->toContain('&shortid=689')
        ->toContain('&inote_2=(179){1}1,2,3,')
        ->toContain('&inote_3=(179){1}4,5,6,')
        ->toContain('E');
});

test('it appends new keys before the first chart block', function () {
    $result = (new MaidataSerializer)->replaceHeaderValues(
        sampleSerializerMaidata(),
        ['tags' => 'tag1, tag2'],
    );

    $lines = explode("\n", $result);
    $tagsIndex = array_search('&tags=tag1, tag2', $lines, true);
    $inoteIndex = array_search('&inote_2=(179){1}1,2,3,', $lines, true);

    expect($tagsIndex)->not->toBeFalse()
        ->and($inoteIndex)->not->toBeFalse()
        ->and($tagsIndex)->toBeLessThan($inoteIndex);
});

test('it removes a key when the value is null or empty', function () {
    $result = (new MaidataSerializer)->replaceHeaderValues(
        sampleSerializerMaidata(),
        ['des' => null],
    );

    expect($result)->not->toContain('&des=Old Designer');
});

test('it drops continuation lines when replacing a multi-line value', function () {
    $contents = "&title=Old\nTitle Line 2\n&artist=Artist\n&inote_1=1,2,3,\n";

    $result = (new MaidataSerializer)->replaceHeaderValues($contents, ['title' => 'New']);

    expect($result)
        ->toContain('&title=New')
        ->not->toContain('Title Line 2');
});

test('it preserves a byte order mark and line endings', function () {
    $contents = "\xEF\xBB\xBF&title=Old\r\n&artist=Artist\r\n&inote_1=1,2,3\r\n";

    $result = (new MaidataSerializer)->replaceHeaderValues($contents, ['title' => 'New']);

    expect($result)
        ->toStartWith("\xEF\xBB\xBF")
        ->toContain("&title=New\r\n")
        ->toContain("&artist=Artist\r\n");
});
