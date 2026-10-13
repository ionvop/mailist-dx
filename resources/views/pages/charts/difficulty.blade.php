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
            <x-revision-timeline :revisions="$revisions" :chart="$chart" :difficulty="$difficulty" :can-edit="$canEdit" />

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
    @else
        @php
            $placeholders = [
                'comments' => ['Comments', 'Discuss this chart with the community.', 'message-circle'],
            ];
        @endphp

        <x-placeholder
            :title="$placeholders[$activeTab][0]"
            :description="$placeholders[$activeTab][1]"
            :icon="$placeholders[$activeTab][2]"
            height="h-48" />
    @endif
@endsection
