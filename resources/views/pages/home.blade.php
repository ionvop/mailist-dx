@extends('layouts.app')

@section('title', 'Home - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Home" subtitle="Discover charts from the community." />

    <div class="space-y-8">
        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">Trending approved charts</h2>
                <a href="{{ route('search', ['status' => 'approved', 'sort' => 'trending']) }}"
                    class="text-sm font-medium text-primary-accent transition hover:brightness-110">
                    View all
                </a>
            </div>

            @if ($trendingApproved->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($trendingApproved as $chart)
                        <x-chart-card :chart="$chart" />
                    @endforeach
                </div>
            @else
                <x-placeholder title="No trending approved charts" description="Approved charts with the most activity over the past week will appear here."
                    icon="trending-up" height="h-32" />
            @endif
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">Most recent approved charts</h2>
                <a href="{{ route('search', ['status' => 'approved']) }}"
                    class="text-sm font-medium text-primary-accent transition hover:brightness-110">
                    View all
                </a>
            </div>

            @if ($recentApproved->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($recentApproved as $chart)
                        <x-chart-card :chart="$chart" />
                    @endforeach
                </div>
            @else
                <x-placeholder title="No approved charts yet" description="The latest charts to be approved by staff will appear here."
                    icon="clock" height="h-32" />
            @endif
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">Trending non-approved charts</h2>
                <a href="{{ route('search', ['sort' => 'trending']) }}"
                    class="text-sm font-medium text-primary-accent transition hover:brightness-110">
                    View all
                </a>
            </div>

            @if ($trendingNonApproved->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($trendingNonApproved as $chart)
                        <x-chart-card :chart="$chart" />
                    @endforeach
                </div>
            @else
                <x-placeholder title="No trending non-approved charts" description="Work in progress, in review, and completed charts gaining traction will appear here."
                    icon="trending-up" height="h-32" />
            @endif
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">Most recent non-approved charts</h2>
                <a href="{{ route('search') }}"
                    class="text-sm font-medium text-primary-accent transition hover:brightness-110">
                    View all
                </a>
            </div>

            @if ($recentNonApproved->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($recentNonApproved as $chart)
                        <x-chart-card :chart="$chart" />
                    @endforeach
                </div>
            @else
                <x-placeholder title="No non-approved charts yet" description="The newest charts uploaded to the platform will appear here."
                    icon="clock" height="h-32" />
            @endif
        </section>
    </div>
@endsection
