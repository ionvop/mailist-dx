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
                <option value="">All</option>
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
                    <x-chart-card :chart="$chart" />
                @endforeach
            </div>

            <div>
                {{ $charts->links() }}
            </div>
        @endif
    </div>
@endsection
