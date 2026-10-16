@extends('layouts.app')

@section('title', 'Chart approval - '.config('app.name', 'mailist DX'))

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

        <div class="space-y-6 rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
            <div>
                <h2 class="text-lg font-bold text-foreground">Approval</h2>
                <p class="mt-1 text-sm text-foreground-accent">
                    Review this chart and approve it, or revert an approved chart back to a reviewable status.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-sm text-foreground-accent">Current status</span>
                <x-status-badge :status="$chart->status" class="px-3 py-1 text-xs" />
            </div>

            @if ($chart->isApproved())
                <form method="POST" action="{{ route('charts.approval.revert', $chart) }}"
                    class="flex justify-end border-t border-border pt-6">
                    @csrf
                    @method('PATCH')

                    <x-button type="submit" variant="secondary" class="w-auto">
                        <x-lucide-undo-2 class="h-4 w-4" /> Revert approval
                    </x-button>
                </form>
            @else
                <form method="POST" action="{{ route('charts.approval.approve', $chart) }}"
                    class="flex justify-end border-t border-border pt-6">
                    @csrf
                    @method('PATCH')

                    <x-button type="submit" class="w-auto">
                        <x-lucide-badge-check class="h-4 w-4" /> Approve chart
                    </x-button>
                </form>
            @endif
        </div>

        @if ($canLeave ?? false)
            @include('pages.charts._leave_edit_access', ['chart' => $chart])
        @endif
    </div>
@endsection
