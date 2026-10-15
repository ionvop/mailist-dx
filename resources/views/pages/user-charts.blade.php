@extends('layouts.app')

@php
    $isUploaded = $mode === 'uploaded';
    $title = $isUploaded ? 'Charts uploaded' : 'Charts liked';
    $emptyDescription = $isUploaded
        ? 'Charts this user publishes will appear here.'
        : 'Charts this user likes will appear here.';
@endphp

@section('title', $title.' - '.$profileUser->username.' - '.config('app.name', 'mailist DX'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('users.show', $profileUser) }}"
            class="inline-flex items-center gap-1.5 text-sm text-foreground-accent transition hover:text-foreground">
            <x-lucide-arrow-left class="h-4 w-4" /> Back to {{ $profileUser->username }}
        </a>
    </div>

    <x-page-header :title="$title" :subtitle="$profileUser->username.' · '.$charts->total().' total'" />

    @if ($charts->isEmpty())
        <x-placeholder :title="$title" :description="$emptyDescription" :icon="$isUploaded ? 'upload' : 'heart'" height="h-48" />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($charts as $chart)
                <x-chart-card :chart="$chart" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $charts->links() }}
        </div>
    @endif
@endsection
