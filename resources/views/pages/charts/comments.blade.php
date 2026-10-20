@extends('layouts.app')

@section('title', 'Comments - '.$chart->title.' - '.config('app.name', 'mailist DX'))

@section('content')
    @include('pages.charts._header', ['chart' => $chart, 'activeTab' => 'comments'])

    @if (session('status'))
        <x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <x-comment-section
        :comments="$comments"
        :store-route="'charts.comments.store'"
        :store-params="[$chart]"
        :destroy-route="'charts.comments.destroy'"
        :destroy-params="[$chart]"
        placeholder="Share your thoughts on this chart..."
        empty-description="Be the first to share your thoughts on this chart."
        delete-description="This permanently removes your comment from the chart. This cannot be undone."
        remove-description="This permanently removes the comment from the chart and notifies its author. This cannot be undone."
    />
@endsection
