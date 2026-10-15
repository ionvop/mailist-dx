@extends('layouts.app')

@section('title', 'Search - '.config('app.name', 'mailist DX'))

@section('content')
    @php
        $hasFilters = $filters['q'] !== '' || $filters['status'] !== null || $filters['sort'] !== 'recent';
    @endphp

    <x-page-header title="Search" subtitle="Explore every chart on the platform." />

    <div class="space-y-6">
        <form method="GET" action="{{ route('search') }}"
            class="flex flex-wrap items-center gap-3 rounded-2xl border border-border bg-background-accent/40 p-4">
            <x-input name="q" :value="request('q')" placeholder="Search title, artist, or note designer"
                class="min-w-[12rem] flex-1" />

            <select name="status"
                class="rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-primary-accent focus:ring-2 focus:ring-primary-accent/40">
                <option value="">All statuses</option>
                @foreach (\App\Enums\ChartStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>

            <div class="inline-flex rounded-lg border border-border bg-background p-1">
                @foreach (['recent' => 'Recent', 'trending' => 'Trending', 'popularity' => 'Popularity'] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="sort" value="{{ $value }}" class="peer sr-only"
                            @checked($filters['sort'] === $value)>
                        <span
                            class="block rounded-md px-3 py-1.5 text-sm font-medium text-foreground-accent transition hover:text-foreground peer-checked:bg-primary-gradient peer-checked:text-primary-foreground peer-checked:shadow">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>

            <x-button type="submit">
                <x-lucide-search class="h-4 w-4" /> Apply
            </x-button>

            @if ($hasFilters)
                <a href="{{ route('search') }}"
                    class="text-sm font-medium text-foreground-accent transition hover:text-foreground">Clear</a>
            @endif
        </form>

        @if ($charts->isEmpty())
            @if ($hasFilters)
                <x-placeholder title="No matching charts" description="Try adjusting or clearing your filters." icon="search-x" height="h-64" />
            @else
                <x-placeholder title="No charts yet" description="Charts will appear here as they are uploaded." icon="layout-grid" height="h-64" />
            @endif
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($charts as $chart)
                    <x-chart-card :chart="$chart" />
                @endforeach
            </div>

            <div>
                {{ $charts->links() }}
            </div>
        @endif
    </div>
@endsection
