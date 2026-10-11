@extends('layouts.app')

@section('title', 'List - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="List" subtitle="Charts you own or have edit access to." />

    <div class="space-y-6">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-placeholder title="Filters" description="Owned, shared with me, and status filters." icon="filter" height="h-20" class="flex-1" />

            <a href="{{ route('charts.upload') }}"
                class="flex h-20 flex-1 flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-primary-accent/40 bg-primary/10 p-6 text-center transition hover:border-primary-accent hover:bg-primary/20">
                <x-lucide-upload class="h-6 w-6 text-primary-accent" />
                <p class="text-sm font-semibold text-foreground">Upload chart</p>
                <p class="max-w-md text-xs text-foreground-accent">Upload a .zip or .adx chart package.</p>
            </a>
        </div>

        @if ($charts->isEmpty())
            <x-placeholder title="Your charts" description="Charts you own or can edit will be listed here." icon="layout-list" height="h-64" />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($charts as $chart)
                    <a href="{{ route('charts.show', $chart) }}"
                        class="group relative flex flex-col rounded-2xl border border-border bg-background-accent/40 transition hover:border-primary-accent/50">
                        <div class="relative aspect-square w-full overflow-hidden rounded-t-2xl bg-background">
                            @if ($chart->art_path)
                                <img src="{{ route('charts.files', [$chart, 'art']) }}" alt="{{ $chart->title }} cover"
                                    class="h-full w-full object-cover transition group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <x-lucide-image class="h-8 w-8 text-foreground-accent" />
                                </div>
                            @endif

                            <x-status-badge :status="$chart->status" class="absolute right-2 top-2 z-10" />
                        </div>

                        @if ($chart->difficulties->isNotEmpty())
                            <div class="z-10 mx-4 -mb-4 -mt-4 h-8">
                                <x-difficulty-badge-list :difficulties="$chart->difficulties" />
                            </div>
                        @endif

                        <div class="flex grow flex-col gap-1 rounded-b-2xl p-4 {{ $chart->difficulties->isNotEmpty() ? 'pt-6' : '' }}">
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-sm font-bold text-foreground">{{ $chart->title }}</p>
                                @if ($chart->is_private)
                                    <x-lucide-lock class="mt-0.5 h-3.5 w-3.5 shrink-0 text-foreground-accent" />
                                @endif
                            </div>
                            <p class="truncate text-xs text-foreground-accent">{{ $chart->artist }}</p>
                            <div class="mt-1 flex items-center gap-1.5 text-xs text-foreground-accent">
                                <x-avatar :user="$chart->uploader" size="xs" />
                                <span class="truncate">{{ $chart->uploader->username }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
