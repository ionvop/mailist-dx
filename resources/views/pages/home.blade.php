@extends('layouts.app')

@section('title', 'Home - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Home" subtitle="Discover charts from the community." />

    <div class="space-y-8">
        <section>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-foreground-accent">Trending approved charts</h2>
            <x-placeholder title="Trending approved charts" description="Charts with the most activity over the past week." icon="trending-up" />
        </section>

        <section>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-foreground-accent">Most recent approved charts</h2>
            <x-placeholder title="Most recent approved charts" description="The latest charts to be approved by staff." icon="clock" />
        </section>

        <section>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-foreground-accent">Trending non-approved charts</h2>
            <x-placeholder title="Trending non-approved charts" description="Work in progress, in review, and completed charts gaining traction." icon="trending-up" />
        </section>

        <section>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-foreground-accent">Most recent non-approved charts</h2>
            <x-placeholder title="Most recent non-approved charts" description="The newest charts uploaded to the platform." icon="clock" />
        </section>
    </div>
@endsection
