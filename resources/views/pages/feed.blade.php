@extends('layouts.app')

@section('title', 'Feed - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Feed" subtitle="Activity from charts you liked and people you follow." />

    <div class="space-y-6">
        <x-placeholder title="Liked charts" description="Charts you have liked, sorted by most recent activity." icon="heart" />
        <x-placeholder title="Following" description="Charts from users you follow, sorted by most recent activity." icon="users" />
    </div>
@endsection
