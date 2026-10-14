@extends('layouts.app')

@section('title', $difficulty->displayName().' - '.$chart->title.' - '.config('app.name', 'mailist DX'))

@section('content')
    @include('pages.charts._difficulty_header', [
        'chart' => $chart,
        'difficulty' => $difficulty,
        'activeTab' => $activeTab,
    ])

    @if (session('status'))
        <x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if ($activeTab === 'code')
        <div @class(['grid gap-6 lg:items-start', 'lg:grid-cols-2' => $preview !== null])>
            @if ($canEdit)
                <form method="POST" action="{{ route('charts.difficulties.update', [$chart, $difficulty]) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <x-code-editor name="code" :value="old('code', $difficulty->code)" :bpm="$chart->audio_bpm" :offset="$chart->audio_offset" :suggest="true" />

                    @error('code')
                        <x-alert variant="error">{{ $message }}</x-alert>
                    @enderror

                    <div class="flex justify-end">
                        <x-button type="submit" class="w-auto">
                            <x-lucide-save class="h-4 w-4" /> Save changes
                        </x-button>
                    </div>
                </form>
            @else
                <div class="space-y-3">
                    <x-code-editor :value="$difficulty->code" :bpm="$chart->audio_bpm" :offset="$chart->audio_offset" :suggest="true" readonly />
                    <p class="text-xs text-foreground-accent">You need edit access to change this chart’s code.</p>
                </div>
            @endif

            @if ($preview !== null)
                <div class="lg:sticky lg:top-6">
                    <x-chart-preview
                        :maidata="$preview['maidata']"
                        :track="$preview['track']"
                        :art="$preview['art']"
                        :pv="$preview['pv']"
                        :level="$preview['level']" />

                    <p class="mt-3 text-center text-xs text-foreground-accent">
                        Powered by
                        <a href="https://github.com/TeamMajdata/MajdataView_web"
                            target="_blank" rel="noopener noreferrer"
                            class="font-medium text-foreground transition hover:text-primary-accent">MajdataView_web</a>
                    </p>
                </div>
            @endif
        </div>

        <x-improvement-composer :chart="$chart" :difficulty="$difficulty" />
    @elseif ($activeTab === 'history')
        @if ($revisions->isEmpty())
            <x-placeholder
                title="No history yet"
                description="Every saved change to this difficulty's code will be snapshotted here."
                icon="history"
                height="h-48" />
        @else
            <x-revision-timeline :revisions="$revisions" :chart="$chart" :difficulty="$difficulty" :can-edit="$canEdit" :focus-revision-id="$focusRevisionId ?? null" />

            <div class="mt-6">
                {{ $revisions->links() }}
            </div>
        @endif
    @elseif ($activeTab === 'improvements')
        <div class="space-y-6">
            {{-- General suggestion form (no code selection required) --}}
            <form method="POST" action="{{ route('charts.difficulties.improvements.store', [$chart, $difficulty]) }}"
                class="space-y-3 rounded-2xl border border-border bg-background-accent/40 p-6">
                @csrf

                <x-textarea name="body" label="Suggest an improvement" rows="3"
                    placeholder="Point out a flaw or propose a change to this chart..." required />

                <p class="text-xs text-foreground-accent">
                    Tip: select code on the Code tab to attach a snippet with line numbers for context.
                </p>

                <div class="flex justify-end">
                    <x-button type="submit" class="w-auto">
                        <x-lucide-send class="h-4 w-4" /> Post suggestion
                    </x-button>
                </div>
            </form>

            {{-- Suggestion list --}}
            <div class="space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">
                    Suggestions
                    @if ($improvements->isNotEmpty())
                        <span class="text-foreground-accent/60">({{ $improvements->count() }})</span>
                    @endif
                </h2>

                @forelse ($improvements as $improvement)
                    <x-improvement-card :improvement="$improvement" :chart="$chart" :difficulty="$difficulty"
                        :can-edit="$canEdit" :revision-options="$revisionOptions" />
                @empty
                    <x-placeholder title="No suggestions yet"
                        description="Be the first to suggest an improvement to this chart."
                        icon="message-square-plus" height="h-32" />
                @endforelse
            </div>
        </div>
    @elseif ($activeTab === 'comments')
        <div class="space-y-6">
            {{-- Comment form --}}
            <form method="POST" action="{{ route('charts.difficulties.comments.store', [$chart, $difficulty]) }}"
                class="space-y-3 rounded-2xl border border-border bg-background-accent/40 p-6">
                @csrf

                <x-textarea name="body" label="Add a comment" rows="3"
                    placeholder="Share your thoughts on this difficulty..." required />

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
                            <span class="text-foreground-accent">{{ $comment->created_at?->diffForHumans() ?? 'Unknown time' }}</span>

                            @if ($comment->user_id === $currentUser?->id)
                                <form method="POST" action="{{ route('charts.difficulties.comments.destroy', [$chart, $difficulty, $comment]) }}" class="ml-auto">
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
                    <x-placeholder title="No comments yet" description="Be the first to share your thoughts on this difficulty."
                        icon="message-circle" height="h-32" />
                @endforelse
            </div>
        </div>
    @elseif ($activeTab === 'settings')
        @php
            $canManage = $chart->isOwnedBy($currentUser ?? null);
        @endphp

        @if (! $canManage)
            <x-placeholder title="Settings are owner-only"
                description="Only the chart owner can change this difficulty's settings."
                icon="lock" height="h-48" />
        @else
            <div class="space-y-6">
                @if ($errors->any())
                    <x-alert variant="error">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif

                {{-- Difficulty details --}}
                <form method="POST" action="{{ route('charts.difficulties.settings.update', [$chart, $difficulty]) }}"
                    class="space-y-6 rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
                    @csrf
                    @method('PATCH')

                    <div>
                        <h2 class="text-lg font-bold text-foreground">Difficulty details</h2>
                        <p class="mt-1 text-sm text-foreground-accent">
                            These values are written back to the chart's <code>maidata.txt</code> and its package.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input name="difficulty_value" label="Difficulty level"
                            :value="$difficulty->difficulty_value" hint="e.g. 13, 13+, 14." />
                        <x-input name="charter" label="Chart designer"
                            :value="$difficulty->charter" hint="Leave empty to fall back to the level's note designer." />
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-border pt-6">
                        <x-button type="submit" class="w-auto">
                            <x-lucide-save class="h-4 w-4" /> Save details
                        </x-button>
                    </div>
                </form>

                {{-- Danger zone --}}
                <div class="rounded-2xl border border-destructive/40 bg-destructive/5 p-6 sm:p-8">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/15">
                            <x-lucide-alert-triangle class="h-5 w-5 text-destructive" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-foreground">Delete difficulty</h2>
                            <p class="mt-1 text-sm text-foreground-accent">
                                Permanently delete this difficulty and its code. This cannot be undone.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" disabled
                            title="Deleting a difficulty is not available yet."
                            class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-destructive/30 bg-background px-4 py-2.5 text-sm font-semibold text-destructive/50 opacity-60">
                            <x-lucide-trash-2 class="h-4 w-4" /> Delete difficulty
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endsection
