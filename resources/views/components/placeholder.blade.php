@props([
    'title',
    'description' => null,
    'icon' => 'construction',
    'height' => 'h-40',
])

<div {{ $attributes->class(['flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-border bg-background-accent/40 p-6 text-center', $height]) }}>
    <x-dynamic-component :component="'lucide-'.$icon" class="h-6 w-6 text-foreground-accent" />
    <p class="text-sm font-semibold text-foreground">{{ $title }}</p>

    @if ($description)
        <p class="max-w-md text-xs text-foreground-accent">{{ $description }}</p>
    @endif

    {{ $slot }}
</div>
