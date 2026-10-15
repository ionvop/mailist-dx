@extends('layouts.app')

@php
    $isFollowers = $mode === 'followers';
    $title = $isFollowers ? 'Followers' : 'Following';
    $subtitle = $isFollowers
        ? "People following {$profileUser->username}."
        : "People {$profileUser->username} follows.";
@endphp

@section('title', $title.' - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header :title="$title" :subtitle="$subtitle" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-border bg-background-accent/40 p-6 sm:flex-row sm:items-center">
            <x-avatar :user="$profileUser" class="h-20 w-20 text-2xl" />

            <div class="min-w-0 flex-1">
                <x-username :user="$profileUser" class="text-lg font-bold" />

                <div class="mt-3 flex gap-6 text-sm">
                    <a href="{{ route('users.following', $profileUser) }}"
                        class="text-foreground-accent transition hover:text-foreground">
                        <span class="font-semibold text-foreground">{{ $profileUser->following_count }}</span> Following
                    </a>
                    <a href="{{ route('users.followers', $profileUser) }}"
                        class="text-foreground-accent transition hover:text-foreground">
                        <span class="font-semibold text-foreground">{{ $profileUser->followers_count }}</span> Followers
                    </a>
                </div>
            </div>

            <x-follow-button :user="$profileUser" :is-following="$profileUser->isFollowedBy($currentUser)" />
        </div>

        @if ($users->isEmpty())
            <x-placeholder
                :title="$isFollowers ? 'No followers yet' : 'Not following anyone yet'"
                :description="$isFollowers
                    ? 'Followers will appear here once people follow this user.'
                    : 'Accounts this user follows will appear here.'"
                icon="users"
                height="h-64" />
        @else
            <div class="divide-y divide-border overflow-hidden rounded-2xl border border-border bg-background-accent/40">
                @foreach ($users as $user)
                    <div class="flex items-center gap-4 p-4">
                        <x-avatar :user="$user" />

                        <div class="min-w-0 flex-1">
                            <x-username :user="$user" />
                            <p class="text-xs text-foreground-accent">
                                {{ $user->followers_count }} Followers &middot; {{ $user->following_count }} Following
                            </p>
                        </div>

                        <x-follow-button :user="$user" :is-following="$user->is_followed_by_current_user" />
                    </div>
                @endforeach
            </div>

            <div>
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
