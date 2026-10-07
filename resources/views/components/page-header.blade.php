@props([
    'title',
    'subtitle' => null,
])

<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ $title }}</h1>

    @if ($subtitle)
        <p class="mt-1 text-sm text-foreground-accent">{{ $subtitle }}</p>
    @endif
</div>
