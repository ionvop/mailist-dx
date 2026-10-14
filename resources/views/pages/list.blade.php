@extends('layouts.app')

@section('title', 'List - '.config('app.name', 'mailist DX'))

@section('content')
    @php
        $hasFilters = $filters['ownership'] !== 'all' || $filters['status'] !== null || $filters['q'] !== '';
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-page-header title="List" subtitle="Charts you own or have edit access to." />

        <x-button href="{{ route('charts.upload') }}">
            <x-lucide-upload class="h-4 w-4" /> Upload chart
        </x-button>
    </div>

    <div class="space-y-6">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <form method="GET" action="{{ route('list') }}"
            class="flex flex-wrap items-center gap-3 rounded-2xl border border-border bg-background-accent/40 p-4">
            <x-input name="q" :value="request('q')" placeholder="Search title or artist" class="min-w-[12rem] flex-1" />

            <div class="inline-flex rounded-lg border border-border bg-background p-1">
                @foreach (['all' => 'All', 'owned' => 'Owned', 'shared' => 'Shared'] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="ownership" value="{{ $value }}" class="peer sr-only"
                            @checked($filters['ownership'] === $value)>
                        <span
                            class="block rounded-md px-3 py-1.5 text-sm font-medium text-foreground-accent transition hover:text-foreground peer-checked:bg-primary-gradient peer-checked:text-primary-foreground peer-checked:shadow">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>

            <select name="status"
                class="rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-primary-accent focus:ring-2 focus:ring-primary-accent/40">
                <option value="">All statuses</option>
                @foreach (\App\Enums\ChartStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>

            <x-button type="submit">
                <x-lucide-search class="h-4 w-4" /> Apply
            </x-button>

            @if ($hasFilters)
                <a href="{{ route('list') }}"
                    class="text-sm font-medium text-foreground-accent transition hover:text-foreground">Clear</a>
            @endif
        </form>

        @if ($charts->isEmpty())
            @if ($hasFilters)
                <x-placeholder title="No matching charts" description="Try adjusting or clearing your filters." icon="search-x" height="h-64" />
            @else
                <x-placeholder title="Your charts" description="Charts you own or can edit will be listed here." icon="layout-list" height="h-64" />
            @endif
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

            <div>
                {{ $charts->links() }}
            </div>
        @endif
    </div>
@endsection
