@extends('layouts.app')

@section('title', 'List - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="List" subtitle="Charts you own or have edit access to." />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-placeholder title="Filters" description="Owned, shared with me, and status filters." icon="filter" height="h-20" class="flex-1" />
            <x-placeholder title="Upload chart" description="Upload a .zip or .adx chart package." icon="upload" height="h-20" class="flex-1" />
        </div>

        <x-placeholder title="Your charts" description="Charts you own or can edit will be listed here." icon="layout-list" height="h-64" />
    </div>
@endsection
