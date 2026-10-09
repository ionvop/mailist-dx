@props([
    'variant' => 'success',
])

@php
    $variants = [
        'success' => 'alert-success',
        'error' => 'alert-error',
        'warning' => 'alert-warning',
    ];
@endphp

<div {{ $attributes->class(['alert text-sm', $variants[$variant] ?? $variants['success']]) }}>
    {{ $slot }}
</div>
