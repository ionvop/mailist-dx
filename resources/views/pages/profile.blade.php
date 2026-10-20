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
                    @if ($profileUser)
                        <a href="{{ route('users.following', $profileUser) }}"
                            class="text-foreground-accent transition hover:text-foreground">
                            <span class="font-semibold text-foreground">{{ $profileUser->following_count }}</span> Following
                        </a>
                        <a href="{{ route('users.followers', $profileUser) }}"
                            class="text-foreground-accent transition hover:text-foreground">
                            <span class="font-semibold text-foreground">{{ $profileUser->followers_count }}</span> Followers
                        </a>
                    @else
                        <span class="text-foreground-accent"><span class="font-semibold text-foreground">0</span> Following</span>
                        <span class="text-foreground-accent"><span class="font-semibold text-foreground">0</span> Followers</span>
                    @endif
                </div>
            </div>

            @if ($isOwnProfile)
                <x-button :href="route('settings')" variant="secondary" class="w-auto">
                    <x-lucide-pencil class="h-4 w-4" /> Edit profile
                </x-button>
            @elseif ($profileUser)
                <x-follow-button :user="$profileUser" :is-following="$profileUser->isFollowedBy($currentUser)" />
            @endif
        </div>

        @if ($profileUser?->bioHtml)
            <div class="rounded-2xl border border-border bg-background-accent/40 p-6">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-foreground-accent">Bio</h2>
                <div class="bio-content text-sm text-foreground">{!! $profileUser->bioHtml !!}</div>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-border bg-background-accent/40 p-6">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-foreground-accent">Bio</h2>
                <p class="text-sm text-foreground-accent">This user has not written a bio yet.</p>
            </div>
        @endif

        {{-- Charts uploaded --}}
        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">
                    Charts uploaded
                    @if ($uploadedCount > 0)
                        <span class="text-foreground-accent/60">({{ $uploadedCount }})</span>
                    @endif
                </h2>
                @if ($uploadedCount > 0)
                    <a href="{{ route('users.charts', $profileUser) }}"
                        class="text-sm font-medium text-primary-accent transition hover:brightness-110">
                        View all
                    </a>
                @endif
            </div>

            @if ($uploadedCharts->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($uploadedCharts as $chart)
                        <x-chart-card :chart="$chart" />
                    @endforeach
                </div>
            @else
                <x-placeholder title="No charts uploaded" description="Charts this user publishes will appear here."
                    icon="upload" height="h-32" />
            @endif
        </section>

        {{-- Charts liked --}}
        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">
                    Charts liked
                    @if ($likedCount > 0)
                        <span class="text-foreground-accent/60">({{ $likedCount }})</span>
                    @endif
                </h2>
                @if ($likedCount > 0)
                    <a href="{{ route('users.liked', $profileUser) }}"
                        class="text-sm font-medium text-primary-accent transition hover:brightness-110">
                        View all
                    </a>
                @endif
            </div>

            @if ($likedCharts->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($likedCharts as $chart)
                        <x-chart-card :chart="$chart" />
                    @endforeach
                </div>
            @else
                <x-placeholder title="No liked charts" description="Charts this user likes will appear here."
                    icon="heart" height="h-32" />
            @endif
        </section>

        {{-- Profile comments --}}
        @if ($profileUser)
            <x-comment-section
                :comments="$profileComments"
                :status="session('status')"
                heading="Profile comments"
                :store-route="'users.comments.store'"
                :store-params="[$profileUser]"
                :destroy-route="'users.comments.destroy'"
                :destroy-params="[$profileUser]"
                form-label="Leave a comment"
                placeholder="Say something nice..."
                guest-prompt="Log in to leave a comment."
                empty-description="Be the first to leave a comment on this profile."
                delete-description="This permanently removes your comment from the profile. This cannot be undone."
                remove-description="This permanently removes the comment from the profile and notifies its author. This cannot be undone."
            />
        @endif
    </div>
@endsection
