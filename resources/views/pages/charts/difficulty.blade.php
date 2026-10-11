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
        <div @class(['grid gap-6 lg:items-start', 'lg:grid-cols-2' => $preview !== null])>
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

            @if ($preview !== null)
                <div class="lg:sticky lg:top-6">
                    <x-chart-preview
                        :maidata="$preview['maidata']"
                        :track="$preview['track']"
                        :art="$preview['art']"
                        :pv="$preview['pv']"
                        :level="$preview['level']" />

                    <p class="mt-3 text-center text-xs text-foreground-accent">
                        Powered by
                        <a href="https://github.com/TeamMajdata/MajdataView_web"
                            target="_blank" rel="noopener noreferrer"
                            class="font-medium text-foreground transition hover:text-primary-accent">MajdataView_web</a>
                    </p>
                </div>
            @endif
        </div>
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
