@props([
    'name',
    'label' => null,
    'value' => null,
    'hint' => null,
    'rows' => 6,
    'placeholder' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->only('class')->class(['space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-foreground">{{ $label }}</label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->class([
            'w-full rounded-lg border bg-background px-3 py-2 text-sm text-foreground placeholder:text-foreground-accent/60 outline-none transition focus:ring-2',
            $hasError
                ? 'border-destructive focus:border-destructive focus:ring-destructive/40'
                : 'border-border focus:border-primary-accent focus:ring-primary-accent/40',
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($hasError)
        <p class="text-xs text-destructive">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="text-xs text-foreground-accent">{{ $hint }}</p>
    @endif
</div>