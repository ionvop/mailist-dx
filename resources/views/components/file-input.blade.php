@props([
    'name',
    'label' => null,
    'hint' => null,
    'accept' => 'image/*',
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
        type="file"
        name="{{ $name }}"
        id="{{ $id }}"
        accept="{{ $accept }}"
        @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->class([
            'block w-full text-sm text-foreground-accent file:mr-3 file:rounded-lg file:border-0 file:bg-primary/20 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-accent hover:file:bg-primary/30',
            $hasError ? 'text-destructive' : '',
        ]) }}
    >

    @if ($hasError)
        <p class="text-xs text-destructive">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="text-xs text-foreground-accent">{{ $hint }}</p>
    @endif
</div>