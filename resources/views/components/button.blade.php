@props([
    'type' => 'submit',
    'href' => null,
    'variant' => 'primary',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition';

    $variants = [
        'primary' => 'bg-primary-gradient text-primary-foreground shadow-lg shadow-primary/20 hover:brightness-110',
        'secondary' => 'border border-border bg-background-accent text-foreground hover:bg-accent',
        'ghost' => 'text-foreground-accent hover:bg-accent hover:text-foreground',
    ];

    $classes = [$base, $variants[$variant] ?? $variants['primary']];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </button>
@endif
