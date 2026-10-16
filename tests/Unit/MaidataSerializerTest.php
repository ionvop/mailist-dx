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

test('it replaces a multi-line chart body in place', function () {
    $contents = implode("\n", [
        '&title=Song',
        '&inote_2=(179){1}1,2,3,',
        '1,2,3,',
        '&lv_2=5',
        '&inote_3=4,5,6,',
        '',
    ]);

    $result = (new MaidataSerializer)->replaceChartCode($contents, '2', "7,8,9,\n4,5,6,");

    expect($result)
        ->toContain("&inote_2=7,8,9,\n4,5,6,")
        ->not->toContain('(179){1}1,2,3,')
        ->toContain('&lv_2=5')
        ->toContain('&inote_3=4,5,6,');
});

test('it appends a chart body when the index is missing', function () {
    $contents = implode("\n", [
        '&title=Song',
        '&inote_2=1,2,3,',
        '&lv_2=5',
        '',
    ]);

    $result = (new MaidataSerializer)->replaceChartCode($contents, '4', '4,5,6,');

    $lines = explode("\n", $result);

    expect($result)->toContain('&inote_4=4,5,6,')
        ->and(array_search('&inote_4=4,5,6,', $lines, true))
        ->toBeLessThan(array_search('&inote_2=1,2,3,', $lines, true));
});

test('it preserves a byte order mark and line endings when replacing chart code', function () {
    $contents = "\xEF\xBB\xBF&title=Song\r\n&inote_2=1,2,3\r\n&lv_2=5\r\n";

    $result = (new MaidataSerializer)->replaceChartCode($contents, '2', "4,5,6,\r\n7,8,9,");

    expect($result)
        ->toStartWith("\xEF\xBB\xBF")
        ->toContain("&inote_2=4,5,6,\r\n7,8,9,"
        )
        ->toContain("&lv_2=5\r\n");
});

test('it removes every header for a chart index, including continuation lines', function () {
    $contents = implode("\n", [
        '&title=Song',
        '&inote_2=(179){1}1,2,3,',
        '1,2,3,',
        '&lv_2=5',
        '&des_2=Designer',
        '&alias_2=Alias',
        '&color_2=ff0000',
        '&smsg_2=Info',
        '&inote_3=4,5,6,',
        '&lv_3=9',
        '',
    ]);

    $result = (new MaidataSerializer)->removeChart($contents, '2');

    expect($result)
        ->not->toContain('&inote_2=')
        ->not->toContain('1,2,3,')
        ->not->toContain('&lv_2=')
        ->not->toContain('&des_2=')
        ->not->toContain('&alias_2=')
        ->not->toContain('&color_2=')
        ->not->toContain('&smsg_2=')
        // Other charts and level-wide keys are untouched.
        ->toContain('&title=Song')
        ->toContain('&inote_3=4,5,6,')
        ->toContain('&lv_3=9');
});

test('it preserves a byte order mark and line endings when removing a chart', function () {
    $contents = "\xEF\xBB\xBF&title=Song\r\n&inote_2=1,2,3\r\n&lv_2=5\r\n&inote_3=4,5,6\r\n";

    $result = (new MaidataSerializer)->removeChart($contents, '2');

    expect($result)
        ->toStartWith("\xEF\xBB\xBF")
        ->not->toContain('&inote_2=')
        ->not->toContain('&lv_2=')
        ->toContain("&inote_3=4,5,6\r\n");
});
