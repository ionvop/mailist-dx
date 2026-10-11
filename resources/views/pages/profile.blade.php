@extends('layouts.app')

@section('title', 'Profile - '.config('app.name', 'mailist DX'))

@section('content')
    @php
        $profileUser = $profileUser ?? $currentUser;
        $isOwnProfile = $profileUser !== null && $profileUser->id === $currentUser?->id;
    @endphp

    <x-page-header title="Profile" subtitle="Your public presence on mailist DX." />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-border bg-background-accent/40 p-6 sm:flex-row sm:items-center">
            <x-avatar :user="$profileUser" class="h-20 w-20 text-2xl" />

            <div class="min-w-0 flex-1">
                <p class="text-lg font-bold text-foreground">{{ $profileUser?->username ?? 'Guest' }}</p>
                @if ($isOwnProfile)
                    <p class="text-sm text-foreground-accent">{{ $profileUser->email }}</p>
                @endif

                <div class="mt-3 flex gap-6 text-sm">
                    <span class="text-foreground-accent"><span class="font-semibold text-foreground">0</span> Following</span>
                    <span class="text-foreground-accent"><span class="font-semibold text-foreground">0</span> Followers</span>
                </div>
            </div>
        </div>

        @if ($profileUser?->bioHtml)
            <div class="rounded-2xl border border-border bg-background-accent/40 p-6">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-foreground-accent">Bio</h2>
                <div class="bio-content text-sm text-foreground">{!! $profileUser->bioHtml !!}</div>
            </div>
        @else
            <x-placeholder title="Bio" description="Markdown-supported biography." icon="file-text" height="h-32" />
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-placeholder title="Charts uploaded" description="Charts this user has published." icon="upload" />
            <x-placeholder title="Charts liked" description="Charts this user has liked." icon="heart" />
        </div>

        <x-placeholder title="Profile comments" description="Comments left on this profile." icon="message-square" height="h-48" />
    </div>
@endsection
