@props([
    'href',
    'icon',
    'label',
    'active' => false,
    'variant' => 'default',
])

@php
    $layouts = [
        'default' => 'btn btn-ghost justify-start gap-3 px-3 text-sm font-medium',
        'emphasized' => 'btn btn-primary gap-3 bg-primary-gradient text-sm font-semibold shadow-lg shadow-primary/20',
        'compact' => 'btn btn-ghost h-auto flex-col gap-1 px-3 py-1 text-xs font-medium',
        'circle' => 'flex h-20 w-20 shrink-0 flex-col items-center justify-center gap-0.5 rounded-full text-xs font-semibold',
    ];

    $states = [
        'default' => $active
            ? 'btn-active bg-primary/20 text-foreground'
            : 'text-foreground-accent hover:bg-accent hover:text-foreground',
        'compact' => $active
            ? 'btn-active bg-primary/20 text-foreground'
            : 'text-foreground-accent hover:bg-accent hover:text-foreground',
        'emphasized' => '',
        'circle' => $active
            ? 'bg-primary-gradient text-primary-foreground shadow-lg shadow-primary/20'
            : 'bg-background text-foreground-accent ring-1 ring-border hover:text-foreground',
    ];

    $iconClasses = [
        'default' => 'h-5 w-5 shrink-0',
        'emphasized' => 'h-5 w-5 shrink-0',
        'compact' => 'h-5 w-5 shrink-0',
        'circle' => 'h-6 w-6 shrink-0 text-primary-accent',
    ];

    $layout = $layouts[$variant] ?? $layouts['default'];
    $state = $states[$variant] ?? $states['default'];
    $iconClass = $iconClasses[$variant] ?? $iconClasses['default'];
@endphp

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([$layout, $state]) }}
>
    <x-dynamic-component :component="'lucide-'.$icon" :class="$iconClass" />
    <span class="truncate">{{ $label }}</span>
</a>
