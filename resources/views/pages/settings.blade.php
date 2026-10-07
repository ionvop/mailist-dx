@extends('layouts.app')

@section('title', 'Settings - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Settings" subtitle="Manage your account preferences." />

    <div class="space-y-6">
        <x-placeholder title="Account" description="Username, email address, and password." icon="user" />
        <x-placeholder title="Profile" description="Avatar and markdown-supported bio." icon="file-text" />
        <x-placeholder title="Notifications" description="Choose which activity sends you a notification." icon="bell" />
    </div>
@endsection
