@props([
    'user' => null,
    'size' => 'md',
])

@php
    $dimensions = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        default => 'h-9 w-9 text-sm',
    };
@endphp

<span {{ $attributes->class(['flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/20 font-bold text-primary-accent', $dimensions]) }}>
    @if ($user?->avatarUrl)
        <img src="{{ $user->avatarUrl }}" alt="{{ $user->username }}" class="h-full w-full object-cover">
    @elseif ($user)
        {{ $user->initial() }}
    @else
        <x-lucide-circle-user class="h-full w-full" />
    @endif
</span>
