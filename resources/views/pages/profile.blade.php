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
        <section class="space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">
                Profile comments
                @if ($commentsCount > 0)
                    <span class="text-foreground-accent/60">({{ $commentsCount }})</span>
                @endif
            </h2>

            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif

            @if ($profileUser)
                @if ($currentUser)
                    <form method="POST" action="{{ route('users.comments.store', $profileUser) }}"
                        class="space-y-3 rounded-2xl border border-border bg-background-accent/40 p-6">
                        @csrf

                        <x-textarea name="body" label="Leave a comment" rows="3"
                            placeholder="Say something nice..." required />

                        <div class="flex justify-end">
                            <x-button type="submit" class="w-auto">
                                <x-lucide-send class="h-4 w-4" /> Post comment
                            </x-button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-background-accent/40 p-6">
                        <p class="text-sm text-foreground-accent">Log in to leave a comment.</p>
                        <x-button href="{{ route('login') }}" class="w-auto">
                            <x-lucide-log-in class="h-4 w-4" /> Log in
                        </x-button>
                    </div>
                @endif

                <div class="space-y-4">
                    @forelse ($profileComments as $comment)
                        <div class="rounded-xl border border-border bg-background-accent/40 p-4">
                            <div class="flex items-center gap-2 text-sm">
                                <x-avatar :user="$comment->user" size="sm" />
                                <x-username :user="$comment->user" />
                                <span class="text-foreground-accent">·</span>
                                <x-local-time :value="$comment->created_at" class="text-foreground-accent" />

                                @if ($comment->user_id === $currentUser?->id)
                                    <x-confirm-dialog
                                        :action="route('users.comments.destroy', [$profileUser, $comment])"
                                        title="Delete this comment?"
                                        description="This permanently removes your comment from the profile. This cannot be undone."
                                        confirm-label="Delete comment"
                                        class="ml-auto"
                                    >
                                        <x-slot:trigger>
                                            <button type="button" @click="open = true"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-foreground-accent transition hover:text-destructive">
                                                <x-lucide-trash-2 class="h-3.5 w-3.5" /> Delete
                                            </button>
                                        </x-slot:trigger>
                                    </x-confirm-dialog>
                                @endif
                            </div>

                            <p class="mt-2 whitespace-pre-line text-sm text-foreground">{{ $comment->body }}</p>
                        </div>
                    @empty
                        <x-placeholder title="No comments yet" description="Be the first to leave a comment on this profile."
                            icon="message-square" height="h-32" />
                    @endforelse
                </div>
            @endif
        </section>
    </div>
@endsection
