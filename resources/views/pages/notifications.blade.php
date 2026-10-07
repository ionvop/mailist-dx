@extends('layouts.app')

@section('title', 'Notifications - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Notifications" subtitle="Recent activity relevant to you." />

    <x-placeholder title="Notifications" description="Likes, comments, follows, and chart status changes will appear here." icon="bell" height="h-64" />
@endsection
