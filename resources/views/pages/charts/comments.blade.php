@extends('layouts.app')

@section('title', 'Comments - '.$chart->title.' - '.config('app.name', 'mailist DX'))

@section('content')
    @include('pages.charts._header', ['chart' => $chart, 'activeTab' => 'comments'])

    @if (session('status'))
        <x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <div class="space-y-6">
        {{-- Comment form --}}
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
                    <div class="flex items-center gap-2 text-sm">
                        <x-avatar :user="$comment->user" size="sm" />
                        <x-username :user="$comment->user" />
                        <span class="text-foreground-accent">·</span>
                        <x-local-time :value="$comment->created_at" class="text-foreground-accent" />

                        @if ($comment->user_id === $currentUser?->id)
                            <form method="POST" action="{{ route('charts.comments.destroy', [$chart, $comment]) }}" class="ml-auto">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-foreground-accent transition hover:text-destructive">
                                    <x-lucide-trash-2 class="h-3.5 w-3.5" /> Delete
                                </button>
                            </form>
                        @endif
                    </div>

                    <p class="mt-2 whitespace-pre-line text-sm text-foreground">{{ $comment->body }}</p>
                </div>
            @empty
                <x-placeholder title="No comments yet" description="Be the first to share your thoughts on this chart."
                    icon="message-square" height="h-32" />
            @endforelse
        </div>
    </div>
@endsection
