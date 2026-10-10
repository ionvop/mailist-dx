@extends('layouts.app')

@section('title', $chart->title.' - '.config('app.name', 'mailist DX'))

@section('content')
    <div x-data="chartTabs" x-cloak>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-bold tracking-tight text-foreground">{{ $chart->title }}</h1>
                <p class="mt-1 text-sm text-foreground-accent">{{ $chart->artist }}</p>
            </div>

            <div class="flex items-center gap-2">
                @if ($chart->is_private)
                    <span class="inline-flex items-center gap-1 rounded-full border border-border bg-background-accent px-3 py-1 text-xs font-semibold text-foreground-accent">
                        <x-lucide-lock class="h-3.5 w-3.5" /> Private
                    </span>
                @endif
                <span class="inline-flex items-center rounded-full border border-border bg-background-accent px-3 py-1 text-xs font-semibold text-foreground-accent">
                    {{ $chart->status->label() }}
                </span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mb-6 flex gap-1 border-b border-border">
            <button
                type="button"
                @click="select('details')"
                class="border-b-2 px-4 py-2 text-sm font-semibold transition"
                :class="active === 'details' ? 'border-primary-accent text-foreground' : 'border-transparent text-foreground-accent hover:text-foreground'"
            >
                Details
            </button>
            <button
                type="button"
                @click="select('comments')"
                class="border-b-2 px-4 py-2 text-sm font-semibold transition"
                :class="active === 'comments' ? 'border-primary-accent text-foreground' : 'border-transparent text-foreground-accent hover:text-foreground'"
            >
                Comments
                @if ($comments->total())
                    <span class="ml-1 text-foreground-accent">{{ $comments->total() }}</span>
                @endif
            </button>
        </div>

        <div x-show="active === 'details'" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
        {{-- Difficulties --}}
        <div class="space-y-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">Difficulties</h2>

            @forelse ($chart->difficulties as $difficulty)
                <div
                    class="flex items-center justify-between gap-3 rounded-xl border border-border bg-background-accent/40 p-4"
                    style="--difficulty-color: var(--color-difficulty-{{ $difficulty->difficulty->value }})"
                >
                    <div class="flex items-center gap-3">
                        <span class="h-8 w-1.5 rounded-full bg-[var(--difficulty-color)]"></span>
                        <div>
                            <p class="text-sm font-bold text-foreground">{{ $difficulty->displayName() }}</p>
                            @if ($difficulty->charter)
                                <p class="text-xs text-foreground-accent">Chart by {{ $difficulty->charter }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($difficulty->difficulty_value)
                        <span class="rounded-lg bg-[var(--difficulty-color)] px-2.5 py-1 text-sm font-bold text-background">
                            {{ $difficulty->difficulty_value }}
                        </span>
                    @endif
                </div>
            @empty
                <x-placeholder title="No difficulties" description="This chart has no difficulties." icon="music" height="h-32" />
            @endforelse
        </div>

        {{-- Details --}}
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="w-full shrink-0 overflow-hidden rounded-2xl border border-border bg-background-accent/40 sm:w-48">
                    @if ($chart->art_path)
                        <img src="{{ route('charts.files', [$chart, 'art']) }}" alt="{{ $chart->title }} cover"
                            class="aspect-square w-full object-cover">
                    @else
                        <div class="flex aspect-square w-full items-center justify-center bg-background">
                            <x-lucide-image class="h-10 w-10 text-foreground-accent" />
                        </div>
                    @endif
                </div>

                <dl class="flex-1 space-y-3 text-sm">
                    <div>
                        <dt class="text-foreground-accent">Artist</dt>
                        <dd class="font-medium text-foreground">{{ $chart->artist }}</dd>
                    </div>
                    <div>
                        <dt class="text-foreground-accent">BPM</dt>
                        <dd class="font-medium text-foreground">{{ $chart->audio_bpm ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-foreground-accent">Note design</dt>
                        <dd class="font-medium text-foreground">{{ $chart->note_designer ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-foreground-accent">Uploaded by</dt>
                        <dd class="font-medium text-foreground">{{ $chart->uploader->username }}</dd>
                    </div>
                </dl>
            </div>

            @if ($chart->tags)
                <div class="flex flex-wrap gap-2">
                    @foreach ($chart->tags as $tag)
                        <span class="rounded-full border border-border bg-background-accent px-3 py-1 text-xs text-foreground-accent">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif

            @if ($chart->track_path)
                <div>
                    <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-foreground-accent">Track preview</h2>
                    <audio controls class="w-full" src="{{ route('charts.files', [$chart, 'track']) }}"></audio>
                </div>
            @endif

            @if ($chart->descriptionHtml)
                <div>
                    <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-foreground-accent">Description</h2>
                    <div class="bio-content text-sm text-foreground">{!! $chart->descriptionHtml !!}</div>
                </div>
            @endif

            @if ($chart->download_path)
                <x-button href="{{ route('charts.files', [$chart, 'download']) }}" variant="secondary" class="w-auto">
                    <x-lucide-download class="h-4 w-4" /> Download package
                </x-button>
            @endif
        </div>
    </div>

        {{-- Comments panel --}}
        <div x-show="active === 'comments'" class="space-y-6">
            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif

            <form method="POST" action="{{ route('comments.store', $chart) }}" class="space-y-3">
                @csrf
                <x-textarea
                    name="body"
                    label="Leave a comment"
                    rows="3"
                    placeholder="Share your thoughts on this chart…"
                    required
                />
                <div class="flex justify-end">
                    <x-button type="submit" class="w-auto">
                        <x-lucide-send class="h-4 w-4" /> Post comment
                    </x-button>
                </div>
            </form>

            <div class="space-y-4">
                @forelse ($comments as $comment)
                    <div class="flex gap-3 rounded-xl border border-border bg-background-accent/40 p-4">
                        <x-avatar :user="$comment->author" size="sm" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-foreground">{{ $comment->author->username }}</span>
                                    <span class="text-xs text-foreground-accent">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>

                                @if ($comment->isDeletableBy($currentUser, $chart->uploader_id))
                                    <form method="POST" action="{{ route('comments.destroy', [$chart, $comment]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-foreground-accent transition hover:text-destructive"
                                        >
                                            <x-lucide-trash-2 class="h-3.5 w-3.5" /> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <p class="mt-1 whitespace-pre-line text-sm text-foreground">{{ $comment->body }}</p>
                        </div>
                    </div>
                @empty
                    <x-placeholder title="No comments yet" description="Be the first to comment on this chart." icon="message-circle" height="h-32" />
                @endforelse
            </div>

            @if ($comments->hasPages())
                <div class="flex justify-center">
                    {{ $comments->fragment('comments')->links() }}
                </div>
            @endif
        </div>
    </div>
