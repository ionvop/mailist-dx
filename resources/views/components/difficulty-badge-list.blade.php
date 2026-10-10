@props([
    'difficulties',
    'max' => 5,
])

@php
    $total = $difficulties->count();
    $visible = $difficulties->slice(-$max)->values();
    $overflow = max(0, $total - $max);
@endphp

@if ($total > 0)
    <div class="relative mx-auto h-8 w-fit">
        <div class="absolute inset-x-0 h-full origin-center skew-x-[-15deg]">
            <div class="h-full w-full rounded-md bg-background-accent transition-colors group-hover:bg-accent"></div>
        </div>

        <div class="relative flex h-8 w-fit flex-row flex-nowrap items-center gap-1 p-1">
            @foreach ($visible as $difficulty)
                <x-difficulty-badge :difficulty="$difficulty" />
            @endforeach

            @if ($overflow > 0)
                <div class="flex w-6 items-center justify-center text-sm font-bold text-foreground">
                    +{{ $overflow }}
                </div>
            @endif
        </div>
    </div>
@endif
