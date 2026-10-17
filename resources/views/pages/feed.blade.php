@extends('layouts.app')

@section('title', 'Feed - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Feed" subtitle="Charts you liked and charts from people you follow." />

    <div class="space-y-6">
        @if ($charts->isEmpty())
            @auth
                <x-placeholder title="Your feed is empty" description="Like charts or follow people to see their activity here." icon="rss" height="h-64" />
            @else
                <x-placeholder title="Log in to see your feed" description="Your feed shows charts you liked and charts from people you follow." icon="rss" height="h-64" />
            @endauth
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($charts as $chart)
                    <x-chart-card :chart="$chart" :revision="$chart->mostRecentRevision()" />
                @endforeach
            </div>

            <div>
                {{ $charts->links() }}
            </div>
        @endif
    </div>
@endsection
