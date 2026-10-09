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
            'textarea w-full text-sm',
            $hasError ? 'textarea-error' : '',
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($hasError)
        <p class="text-xs text-destructive">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="text-xs text-foreground-accent">{{ $hint }}</p>
    @endif
</div>