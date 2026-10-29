<?php

use App\Services\Maidata\MaidataLineEndings;

test('it converts CRLF line endings to LF', function () {
    expect(MaidataLineEndings::normalize("a\r\nb\r\nc"))->toBe("a\nb\nc");
});

test('it converts lone CR line endings to LF', function () {
    expect(MaidataLineEndings::normalize("a\rb\rc"))->toBe("a\nb\nc");
});

test('it converts mixed line endings to LF', function () {
    expect(MaidataLineEndings::normalize("a\r\nb\rc\nd"))->toBe("a\nb\nc\nd");
});

test('it leaves LF contents untouched', function () {
    $contents = "&title=Song\n&inote_1=1,2,3,\n";

    expect(MaidataLineEndings::normalize($contents))->toBe($contents);
});

test('it preserves a leading byte order mark', function () {
    expect(MaidataLineEndings::normalize("\xEF\xBB\xBF&title=Song\r\n"))
        ->toBe("\xEF\xBB\xBF&title=Song\n");
});

test('it detects carriage returns', function () {
    expect(MaidataLineEndings::hasCarriageReturns("a\r\nb"))->toBeTrue()
        ->and(MaidataLineEndings::hasCarriageReturns("a\rb"))->toBeTrue()
        ->and(MaidataLineEndings::hasCarriageReturns("a\nb"))->toBeFalse();
});
