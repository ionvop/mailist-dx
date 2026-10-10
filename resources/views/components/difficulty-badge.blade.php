@props([
    'difficulty',
])

@php
    $color = $difficulty->difficulty_color
        ?: 'var(--color-difficulty-'.$difficulty->difficulty->value.')';

    $value = $difficulty->difficulty_value;
    $label = filled($value) && mb_strlen($value) <= 3 ? $value : '???';
@endphp

<div class="relative h-6 w-9 shrink-0" style="--difficulty-color: {{ $color }}">
    <div class="absolute inset-0 origin-center skew-x-[-15deg]">
        <div class="absolute inset-0 rounded-md bg-[var(--difficulty-color)]"></div>
        <div class="absolute inset-0 rounded-md bg-linear-to-b from-black/0 to-black/25"></div>
        <div class="absolute inset-0.5 rounded-sm bg-[var(--difficulty-color)]"></div>
        <div class="absolute inset-0.5 rounded-sm bg-black/25"></div>
    </div>

    <div class="absolute inset-0 flex items-center justify-center text-xs font-bold text-foreground">
        {{ $label }}
    </div>
</div>
