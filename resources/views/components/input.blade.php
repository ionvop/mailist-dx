@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'inputmode' => null,
    'autocomplete' => null,
    'placeholder' => null,
    'autofocus' => false,
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

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($name, $value) }}"
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autofocus) autofocus @endif
        @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->class([
            'w-full rounded-lg border bg-background px-3 py-2 text-sm text-foreground placeholder:text-foreground-accent/60 outline-none transition focus:ring-2',
            $hasError
                ? 'border-destructive focus:border-destructive focus:ring-destructive/40'
                : 'border-border focus:border-primary-accent focus:ring-primary-accent/40',
        ]) }}
    >

    @if ($hasError)
        <p class="text-xs text-destructive">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="text-xs text-foreground-accent">{{ $hint }}</p>
    @endif
</div>
