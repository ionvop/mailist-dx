@props([
    'user' => null,
    'align' => 'right',
])

@php
    $alignment = $align === 'left' ? 'dropdown-start' : 'dropdown-end';
@endphp

<div class="dropdown {{ $alignment }}" x-data="{ open: false }" @keydown.escape.window="open = false" :class="{ 'dropdown-open': open }">
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="menu"
        title="{{ $user?->username ?? 'Guest' }}"
        class="btn btn-ghost h-auto min-h-0 gap-1.5 rounded-full px-1"
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
        class="dropdown-content z-50 mt-2 w-64 overflow-hidden rounded-2xl border border-border bg-background shadow-2xl"
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

        <ul class="menu w-full p-1.5">
            @if ($user)
                <li>
                    <a href="{{ route('profile') }}" role="menuitem" class="gap-3 text-foreground-accent hover:text-foreground">
                        <x-lucide-user class="h-4 w-4" />
                        Profile
                    </a>
                </li>
            @endif

            <li>
                <a href="{{ route('settings') }}" role="menuitem" class="gap-3 text-foreground-accent hover:text-foreground">
                    <x-lucide-settings class="h-4 w-4" />
                    Settings
                </a>
            </li>
        </ul>

        <ul class="menu w-full border-t border-border p-1.5">
            @if ($user)
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" role="menuitem" class="gap-3 text-destructive hover:bg-destructive/10">
                            <x-lucide-log-out class="h-4 w-4" />
                            Log out
                        </button>
                    </form>
                </li>
            @else
                <li>
                    <a href="{{ route('login') }}" role="menuitem" class="gap-3 text-foreground-accent hover:text-foreground">
                        <x-lucide-log-in class="h-4 w-4" />
                        Log in
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
