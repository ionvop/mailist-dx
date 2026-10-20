@extends('layouts.app')

@section('title', 'Chart moderation - '.config('app.name', 'mailist DX'))

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
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/15">
                    <x-lucide-shield class="h-5 w-5 text-primary-accent" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-foreground">Moderation</h2>
                    <p class="mt-1 text-sm text-foreground-accent">
                        @if ($chart->is_delisted)
                            This chart is delisted and hidden from public listings. Restoring it makes it
                            publicly visible again.
                        @else
                            Delisting hides this chart from public listings. The owner cannot undo this; only
                            moderators can restore the chart.
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-sm text-foreground-accent">Current visibility</span>
                @if ($chart->is_delisted)
                    <span class="inline-flex items-center gap-1 rounded-full border border-destructive/40 bg-destructive/10 px-3 py-1 text-xs font-semibold text-destructive">
                        <x-lucide-eye-off class="h-3.5 w-3.5" /> Delisted
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full border border-border bg-background-accent px-3 py-1 text-xs font-semibold text-foreground-accent">
                        <x-lucide-eye class="h-3.5 w-3.5" /> Public
                    </span>
                @endif
            </div>

            <div class="flex justify-end border-t border-border pt-6">
                @if ($chart->is_delisted)
                    <form method="POST" action="{{ route('charts.moderation.restore', $chart) }}">
                        @csrf
                        @method('PATCH')

                        <x-button type="submit" variant="secondary" class="w-auto">
                            <x-lucide-eye class="h-4 w-4" /> Restore chart
                        </x-button>
                    </form>
                @else
                    <x-confirm-dialog
                        :action="route('charts.moderation.delist', $chart)"
                        title="Delist “{{ $chart->title }}”?"
                        description="This hides the chart from public listings. The owner cannot undo this; only moderators can restore it."
                        confirm-label="Delist chart"
                    >
                        <x-slot:trigger>
                            <button type="button" @click="open = true"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-destructive/50 bg-background px-4 py-2.5 text-sm font-semibold text-destructive transition hover:bg-destructive/10">
                                <x-lucide-eye-off class="h-4 w-4" /> Delist chart
                            </button>
                        </x-slot:trigger>
                    </x-confirm-dialog>
                @endif
            </div>
        </div>

        @if ($canLeave ?? false)
            @include('pages.charts._leave_edit_access', ['chart' => $chart])
        @endif
    </div>
@endsection
