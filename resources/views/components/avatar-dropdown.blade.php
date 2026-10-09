@props([
    'user' => null,
    'align' => 'right',
])

@php
    $alignment = $align === 'left' ? 'left-0' : 'right-0';
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="menu"
        title="{{ $user?->username ?? 'Guest' }}"
        class="flex items-center gap-1.5 rounded-full transition hover:opacity-90"
    >
        <x-avatar :user="$user" />
        <x-lucide-chevron-down class="h-4 w-4 text-foreground-accent" />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        @click.outside="open = false"
        role="menu"
        class="absolute {{ $alignment }} top-full z-50 mt-2 w-64 overflow-hidden rounded-2xl border border-border bg-background shadow-2xl"
    >
        @if ($user)
            <div class="border-b border-border p-4">
                <div class="flex items-center gap-3">
                    <x-avatar :user="$user" size="sm" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-foreground">{{ $user->username }}</p>
                        <p class="truncate text-xs text-foreground-accent">{{ $user->email }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="p-1.5">
            @if ($user)
                <a href="{{ route('profile') }}" role="menuitem" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-foreground-accent transition hover:bg-accent hover:text-foreground">
                    <x-lucide-user class="h-4 w-4" />
                    Profile
                </a>
            @endif

            <a href="{{ route('settings') }}" role="menuitem" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-foreground-accent transition hover:bg-accent hover:text-foreground">
                <x-lucide-settings class="h-4 w-4" />
                Settings
            </a>
        </div>

        <div class="border-t border-border p-1.5">
            @if ($user)
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" role="menuitem" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-destructive transition hover:bg-destructive/10">
                        <x-lucide-log-out class="h-4 w-4" />
                        Log out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" role="menuitem" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-foreground-accent transition hover:bg-accent hover:text-foreground">
                    <x-lucide-log-in class="h-4 w-4" />
                    Log in
                </a>
            @endif
        </div>
    </div>
</div>
