@props([
    'user',
    'isFollowing' => false,
])

@php
    $currentUser = $currentUser ?? auth()->user();
    $isOwnProfile = $currentUser !== null && $currentUser->id === $user->id;
@endphp

@if ($isOwnProfile)
    {{-- A user cannot follow themselves. --}}
@elseif ($currentUser === null)
    <x-button :href="route('login')" variant="secondary" class="w-auto">
        <x-lucide-user-plus class="h-4 w-4" /> Follow
    </x-button>
@elseif ($isFollowing)
    <form method="POST" action="{{ route('users.follow.destroy', $user) }}">
        @csrf
        @method('DELETE')
        <x-button type="submit" variant="secondary" class="w-auto" aria-pressed="true">
            <x-lucide-user-check class="h-4 w-4 text-primary-accent" /> Following
        </x-button>
    </form>
@else
    <form method="POST" action="{{ route('users.follow.store', $user) }}">
        @csrf
        <x-button type="submit" variant="secondary" class="w-auto" aria-pressed="false">
            <x-lucide-user-plus class="h-4 w-4" /> Follow
        </x-button>
    </form>
@endif
