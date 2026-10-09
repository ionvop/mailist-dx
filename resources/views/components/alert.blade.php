@props([
    'variant' => 'success',
])

@php
    $variants = [
        'success' => 'border-primary-accent/30 bg-primary-accent/10 text-primary-accent',
        'error' => 'border-destructive/30 bg-destructive/10 text-destructive',
    ];
@endphp

<div {{ $attributes->class(['rounded-lg border px-4 py-3 text-sm', $variants[$variant] ?? $variants['success']]) }}>
    {{ $slot }}
</div>
