@props([
    'action',
    'method' => 'DELETE',
    'title' => 'Are you sure?',
    'description' => 'This action cannot be undone.',
    'confirmLabel' => 'Delete',
    'variant' => 'destructive',
])

@php
    $confirmClasses = match ($variant) {
        'primary' => 'bg-primary-gradient text-primary-foreground shadow-lg shadow-primary/20 hover:brightness-110',
        default => 'bg-destructive text-white hover:brightness-110',
    };
@endphp

<div x-data="{ open: false }" {{ $attributes->class('contents') }}>
    {{-- Trigger --}}
    {{ $trigger }}

    {{-- Confirmation modal --}}
    <div x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
        @keydown.escape.window="open = false">
        <div class="w-full max-w-md rounded-2xl border border-border bg-background p-6 shadow-2xl" @click.outside="open = false">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/15">
                    <x-lucide-alert-triangle class="h-5 w-5 text-destructive" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-foreground">{{ $title }}</h2>
                    <p class="mt-1 text-sm text-foreground-accent">{{ $description }}</p>
                </div>
            </div>

            {{ $slot }}

            <form method="POST" action="{{ $action }}" class="mt-6 flex items-center justify-end gap-3">
                @csrf
                @method($method)

                <button type="button" @click="open = false"
                    class="inline-flex items-center justify-center rounded-lg border border-border bg-background-accent px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-accent">
                    Cancel
                </button>
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold transition {{ $confirmClasses }}">
                    {{ $confirmLabel }}
                </button>
            </form>
        </div>
    </div>
</div>
