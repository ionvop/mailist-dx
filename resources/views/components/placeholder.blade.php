@props([
    'title',
    'description' => null,
    'icon' => 'construction',
    'height' => 'h-40',
])

<div {{ $attributes->class(['card card-border border-dashed bg-base-200/40', $height]) }}>
    <div class="card-body items-center justify-center gap-2 p-6 text-center">
        <x-dynamic-component :component="'lucide-'.$icon" class="h-6 w-6 text-foreground-accent" />
        <p class="text-sm font-semibold text-foreground">{{ $title }}</p>

        @if ($description)
            <p class="max-w-md text-xs text-foreground-accent">{{ $description }}</p>
        @endif

        {{ $slot }}
    </div>
</div>
