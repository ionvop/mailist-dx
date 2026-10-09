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
            'input w-full text-sm',
            $hasError ? 'input-error' : '',
        ]) }}
    >

    @if ($hasError)
        <p class="text-xs text-destructive">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="text-xs text-foreground-accent">{{ $hint }}</p>
    @endif
</div>
