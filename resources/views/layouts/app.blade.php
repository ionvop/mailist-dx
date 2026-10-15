<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'mailist DX'))</title>

        <link rel="icon" type="image/svg+xml" href="/favicon.ico">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="flex min-h-screen flex-col lg:flex-row">
            {{-- Left navigation panel (desktop) --}}
            <aside class="hidden w-64 shrink-0 flex-col border-r border-border bg-background-accent lg:sticky lg:top-0 lg:flex lg:h-screen lg:overflow-y-auto">
                <a href="{{ route('home') }}" class="flex items-center gap-2 px-5 py-5">
                    <x-lucide-music class="h-7 w-7 text-primary-accent" />
                    <span class="text-lg font-bold tracking-tight text-foreground">{{ config('app.name', 'mailist DX') }}</span>
                </a>

                <nav class="flex flex-1 flex-col gap-1 px-3 pb-4">
                    <x-nav-link :href="route('home')" icon="home" label="Home" :active="request()->routeIs('home')" />
                    <x-nav-link :href="route('search')" icon="search" label="Search" :active="request()->routeIs('search')" />
                    <x-nav-link :href="route('feed')" icon="rss" label="Feed" :active="request()->routeIs('feed')" />

                    @if ($currentUser)
                        <x-nav-link :href="route('profile')" icon="user" label="Profile" :active="request()->routeIs('profile')" />
                    @else
                        <x-nav-link :href="route('login')" icon="log-in" label="Login" :active="request()->routeIs('login')" />
                    @endif

                    <x-nav-link :href="route('list')" icon="layout-list" label="List" :active="request()->routeIs('list')" variant="emphasized" class="mt-auto self-start" />
                </nav>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                {{-- Header --}}
                <header class="sticky top-0 z-40 border-b border-border bg-background/90 backdrop-blur">
                    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6">
                        {{-- Brand (mobile only) --}}
                        <a href="{{ route('home') }}" class="flex items-center gap-2 lg:hidden">
                            <x-lucide-music class="h-6 w-6 text-primary-accent" />
                            <span class="text-base font-bold tracking-tight text-foreground">{{ config('app.name', 'mailist DX') }}</span>
                        </a>

                        <div class="hidden lg:block"></div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('notifications') }}"
                                title="Notifications"
                                class="relative flex h-9 w-9 items-center justify-center rounded-full text-foreground-accent transition hover:bg-accent hover:text-foreground"
                            >
                                <x-lucide-bell class="h-5 w-5" />
                            </a>

                            <x-avatar-dropdown :user="$currentUser" />
                        </div>
                    </div>
                </header>

                {{-- Content --}}
                <main class="flex-1 bg-background-content px-4 py-6 pb-28 sm:px-6 lg:pb-6">
                    <div class="mx-auto w-full max-w-5xl">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        {{-- Bottom navigation bar (mobile) --}}
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-background-accent lg:hidden">
            <div class="flex items-center justify-around px-2 py-2">
                <x-nav-link :href="route('home')" icon="home" label="Home" :active="request()->routeIs('home')" variant="compact" />
                <x-nav-link :href="route('search')" icon="search" label="Search" :active="request()->routeIs('search')" variant="compact" />
                <x-nav-link :href="route('list')" icon="layout-list" label="List" :active="request()->routeIs('list')" variant="circle" class="-mt-6 translate-y-3" />
                <x-nav-link :href="route('feed')" icon="rss" label="Feed" :active="request()->routeIs('feed')" variant="compact" />

                @if ($currentUser)
                    <x-nav-link :href="route('profile')" icon="user" label="Profile" :active="request()->routeIs('profile')" variant="compact" />
                @else
                    <x-nav-link :href="route('login')" icon="log-in" label="Login" :active="request()->routeIs('login')" variant="compact" />
                @endif
            </div>
        </nav>
    </body>
</html>
