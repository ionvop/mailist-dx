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
                    class="flex flex-col gap-4 border-t border-border pt-6 sm:flex-row sm:items-end">
                    @csrf
                    @method('PATCH')

                    <div class="min-w-0 flex-1 space-y-1.5">
                        <label for="status" class="block text-sm font-medium text-foreground">Revert to</label>
                        <select name="status" id="status"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-primary-accent focus:ring-2 focus:ring-primary-accent/40">
                            @foreach ($revertStatuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-button type="submit" variant="secondary" class="w-auto shrink-0">
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
    </div>
@endsection
