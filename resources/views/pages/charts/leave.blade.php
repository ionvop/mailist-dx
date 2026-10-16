@extends('layouts.app')

@section('title', 'Chart settings - '.config('app.name', 'mailist DX'))

@section('content')
    @include('pages.charts._header', ['chart' => $chart, 'activeTab' => 'settings'])

    <div class="space-y-6">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert variant="error">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        @include('pages.charts._leave_edit_access', ['chart' => $chart])
    </div>
@endsection
