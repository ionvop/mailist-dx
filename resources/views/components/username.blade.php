@props([
    'user' => null,
])

@if ($user)
    <a href="{{ route('users.show', $user) }}"
        {{ $attributes->class(['font-medium text-foreground transition hover:text-primary-accent']) }}>
        {{ $user->username }}
    </a>
@else
    <span {{ $attributes->class(['font-medium text-foreground-accent']) }}>Deleted user</span>
@endif
