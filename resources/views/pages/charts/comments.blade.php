@extends('layouts.app')

@section('title', 'Comments - '.$chart->title.' - '.config('app.name', 'mailist DX'))

@section('content')
    @include('pages.charts._header', ['chart' => $chart, 'activeTab' => 'comments'])

    @if (session('status'))
        <x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <div class="space-y-6">
        {{-- Comment form --}}
        @if ($currentUser)
            <form method="POST" action="{{ route('charts.comments.store', $chart) }}"
                class="space-y-3 rounded-2xl border border-border bg-background-accent/40 p-6">
                @csrf

                <x-textarea name="body" label="Add a comment" rows="3"
                    placeholder="Share your thoughts on this chart..." required />

                <div class="flex justify-end">
                    <x-button type="submit" class="w-auto">
                        <x-lucide-send class="h-4 w-4" /> Post comment
                    </x-button>
                </div>
            </form>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-background-accent/40 p-6">
                <p class="text-sm text-foreground-accent">Log in to join the conversation.</p>
                <x-button href="{{ route('login') }}" class="w-auto">
                    <x-lucide-log-in class="h-4 w-4" /> Log in
                </x-button>
            </div>
        @endif

        {{-- Comment list --}}
        <div class="space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">
                Comments
                @if ($comments->isNotEmpty())
                    <span class="text-foreground-accent/60">({{ $comments->count() }})</span>
                @endif
            </h2>

            @forelse ($comments as $comment)
                <div class="rounded-xl border border-border bg-background-accent/40 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <x-avatar :user="$comment->user" size="sm" />

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-username :user="$comment->user" class="text-sm font-semibold" />
                                </div>

                                <p class="mt-0.5 text-xs text-foreground-accent">
                                    <x-local-time :value="$comment->created_at" />
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($comment->user_id === $currentUser?->id)
                                <x-confirm-dialog
                                    :action="route('charts.comments.destroy', [$chart, $comment])"
                                    title="Delete this comment?"
                                    description="This permanently removes your comment from the chart. This cannot be undone."
                                    confirm-label="Delete comment"
                                >
                                    <x-slot:trigger>
                                        <button type="button" @click="open = true"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-foreground-accent transition hover:text-destructive">
                                            <x-lucide-trash-2 class="h-3.5 w-3.5" /> Delete
                                        </button>
                                    </x-slot:trigger>
                                </x-confirm-dialog>
                            @elseif ($currentUser?->isModerator())
                                <x-confirm-dialog
                                    :action="route('charts.comments.destroy', [$chart, $comment])"
                                    title="Remove this comment?"
                                    description="This permanently removes the comment from the chart and notifies its author. This cannot be undone."
                                    confirm-label="Remove comment"
                                >
                                    <x-slot:trigger>
                                        <button type="button" @click="open = true"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-foreground-accent transition hover:text-destructive">
                                            <x-lucide-shield class="h-3.5 w-3.5" /> Remove
                                        </button>
                                    </x-slot:trigger>
                                </x-confirm-dialog>
                            @endif
                        </div>
                    </div>

                    <p class="mt-3 whitespace-pre-line text-sm text-foreground">{{ $comment->body }}</p>
                </div>
            @empty
                <x-placeholder title="No comments yet" description="Be the first to share your thoughts on this chart."
                    icon="message-square" height="h-32" />
            @endforelse
        </div>
    </div>
@endsection
