@extends('layouts.app')

@section('title', $difficulty->displayName().' - '.$chart->title.' - '.config('app.name', 'mailist DX'))

@section('content')
    @include('pages.charts._difficulty_header', [
        'chart' => $chart,
        'difficulty' => $difficulty,
        'activeTab' => $activeTab,
    ])

    @if (session('status'))
        <x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if ($activeTab === 'code')
        @if ($canEdit)
            <form method="POST" action="{{ route('charts.difficulties.update', [$chart, $difficulty]) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <x-code-editor name="code" :value="old('code', $difficulty->code)" />

                @error('code')
                    <x-alert variant="error">{{ $message }}</x-alert>
                @enderror

                <div class="flex justify-end">
                    <x-button type="submit" class="w-auto">
                        <x-lucide-save class="h-4 w-4" /> Save changes
                    </x-button>
                </div>
            </form>
        @else
            <div class="space-y-3">
                <x-code-editor :value="$difficulty->code" readonly />
                <p class="text-xs text-foreground-accent">You need edit access to change this chart’s code.</p>
            </div>
        @endif
    @else
        @php
            $placeholders = [
                'improvements' => ['Improvements', 'Suggest and track changes to this chart.', 'message-square-plus'],
                'comments' => ['Comments', 'Discuss this chart with the community.', 'message-circle'],
                'history' => ['History', 'Every saved change is snapshotted here.', 'history'],
            ];
        @endphp

        <x-placeholder
            :title="$placeholders[$activeTab][0]"
            :description="$placeholders[$activeTab][1]"
            :icon="$placeholders[$activeTab][2]"
            height="h-48" />
    @endif
@endsection
