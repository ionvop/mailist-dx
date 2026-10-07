@props([
    'href',
    'icon',
    'label',
    'active' => false,
    'emphasized' => false,
])

@php
    $base = 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition';

    $state = $active
        ? 'bg-primary/20 text-foreground'
        : 'text-foreground-accent hover:bg-accent hover:text-foreground';

    $emphasis = $emphasized
        ? 'bg-primary-gradient text-primary-foreground shadow-lg shadow-primary/20 hover:brightness-110'
        : '';
@endphp

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([$base, $state, $emphasis]) }}
>
    <x-dynamic-component :component="'lucide-'.$icon" class="h-5 w-5 shrink-0" />
    <span class="truncate">{{ $label }}</span>
</a>
