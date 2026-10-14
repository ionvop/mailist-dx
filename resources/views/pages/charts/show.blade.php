@extends('layouts.app')

@section('title', $chart->title.' - '.config('app.name', 'mailist DX'))

@section('content')
    @include('pages.charts._header', ['chart' => $chart, 'activeTab' => 'details'])

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
        {{-- Difficulties --}}
        <div class="space-y-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">Difficulties</h2>

            @forelse ($chart->difficulties as $difficulty)
                <a
                    href="{{ route('charts.difficulties.show', [$chart, $difficulty]) }}"
                    class="flex items-center justify-between gap-3 rounded-xl border border-border bg-background-accent/40 p-4 transition hover:border-primary-accent/50 hover:bg-background-accent"
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

                    <div class="flex items-center gap-2">
                        @if ($difficulty->difficulty_value)
                            <x-difficulty-badge :difficulty="$difficulty" />
                        @endif
                        <x-lucide-chevron-right class="h-4 w-4 shrink-0 text-foreground-accent" />
                    </div>
                </a>
            @empty
                <x-placeholder title="No difficulties" description="This chart has no difficulties." icon="music" height="h-32" />
            @endforelse
        </div>

        {{-- Details --}}
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="w-full shrink-0 overflow-hidden rounded-2xl border border-border bg-background-accent/40 sm:w-78">
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
                        <dt class="text-foreground-accent">Title</dt>
                        <dd class="font-medium text-foreground">{{ $chart->title }}</dd>
                    </div>
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
                        <dd>
                            <a href="{{ route('users.show', $chart->uploader) }}"
                                class="inline-flex items-center gap-2 font-medium text-foreground transition hover:text-primary-accent">
                                <x-avatar :user="$chart->uploader" size="xs" />
                                {{ $chart->uploader->username }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-foreground-accent">Uploaded</dt>
                        <dd class="font-medium text-foreground">
                            <x-local-time :value="$chart->created_at" />
                        </dd>
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
                <div class="flex flex-wrap gap-2">
                    <x-button href="{{ route('charts.files', [$chart, 'download']) }}" variant="secondary" class="w-auto">
                        <x-lucide-download class="h-4 w-4" /> Download package
                    </x-button>
                    <x-button href="{{ route('charts.files', [$chart, 'adx']) }}" variant="secondary" class="w-auto">
                        <x-lucide-download class="h-4 w-4" /> Download ADX
                    </x-button>
                </div>
            @endif
        </div>
    </div>
@endsection
