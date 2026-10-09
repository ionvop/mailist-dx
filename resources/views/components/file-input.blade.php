@props([
    'name',
    'label' => null,
    'hint' => null,
    'accept' => 'image/*',
    'required' => false,
    'compact' => false,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);

    $dropzoneClasses = $compact
        ? 'flex items-center gap-3 rounded-lg border px-3 py-2'
        : 'flex min-h-28 flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 text-center';

    $stateClasses = $hasError
        ? 'border-destructive'
        : 'border-border hover:border-primary-accent/60';
@endphp

<div
    {{ $attributes->only('class')->class(['flex flex-col space-y-1.5']) }}
    x-data="{ filename: '', dragging: false }"
    @change="filename = $event.target.files[0]?.name ?? ''"
>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-foreground">{{ $label }}</label>
    @endif

    <label
        for="{{ $id }}"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="dragging = false; $refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change', { bubbles: true }))"
        class="{{ $dropzoneClasses }} {{ $stateClasses }} flex-1 cursor-pointer bg-background-accent/40 outline-none transition focus-within:ring-2 focus-within:ring-primary-accent/40"
        :class="dragging ? 'border-primary-accent bg-primary/10' : ''"
    >
        <input
            type="file"
            name="{{ $name }}"
            id="{{ $id }}"
            accept="{{ $accept }}"
            x-ref="input"
            @if ($required) required @endif
            {{ $attributes->except(['class', 'id'])->class('sr-only') }}
        >

        @if ($compact)
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-primary/20 px-2.5 py-1 text-xs font-medium text-primary-accent">
                <x-lucide-upload class="h-3.5 w-3.5" />
                Choose file
            </span>
            <span class="truncate text-xs text-foreground-accent" x-text="filename || 'No file chosen'"></span>
        @else
            <x-lucide-upload class="h-6 w-6 text-foreground-accent" />
            <span class="inline-flex items-center gap-2 rounded-lg bg-primary/20 px-3 py-1.5 text-sm font-medium text-primary-accent">
                Choose file
            </span>
            <span class="text-xs text-foreground-accent" x-text="filename || 'No file chosen'"></span>
        @endif
    </label>

    @if ($hasError)
        <p class="text-xs text-destructive">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="text-xs text-foreground-accent">{{ $hint }}</p>
    @endif
</div>