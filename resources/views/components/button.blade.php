@props([
    'type' => 'submit',
    'href' => null,
    'variant' => 'primary',
])

@php
    $base = 'btn w-full gap-2 text-sm font-semibold';

    $variants = [
        'primary' => 'btn-primary bg-primary-gradient shadow-lg shadow-primary/20',
        'secondary' => 'btn-outline border-border bg-background-accent text-foreground hover:border-border hover:bg-accent',
        'ghost' => 'btn-ghost text-foreground-accent hover:bg-accent hover:text-foreground',
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
