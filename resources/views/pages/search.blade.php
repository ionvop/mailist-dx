@extends('layouts.app')

@section('title', 'Search - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Search" subtitle="Explore every chart on the platform." />

    <div class="space-y-6">
        <x-placeholder title="Search bar" description="Full-text search across chart titles, artists, and note designers." icon="search" height="h-24" />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-placeholder title="Filter" description="WIP, For Review, Completed, Approved." icon="filter" height="h-32" />
            <x-placeholder title="Sort" description="Popularity, Trending (likes received within a week), Recent." icon="arrow-up-down" height="h-32" />
        </div>

        <x-placeholder title="Results" description="Matching charts will be listed here." icon="layout-grid" height="h-64" />
    </div>
@endsection
