@props([
    'value' => null,
    'fallback' => 'Unknown time',
])

{{--
    Renders a timestamp whose absolute value is localized to the visitor's
    timezone on the client. The server always emits the UTC value in the
    `datetime` attribute (and a UTC tooltip as a no-JS fallback); the relative
    text is timezone-independent and rendered server-side.
--}}
@if ($value)
    <time datetime="{{ $value->toIso8601String() }}" data-local-time
        title="{{ $value->toDayDateTimeString() }}" {{ $attributes }}>{{ $value->diffForHumans() }}</time>
@else
    <span {{ $attributes }}>{{ $fallback }}</span>
@endif
