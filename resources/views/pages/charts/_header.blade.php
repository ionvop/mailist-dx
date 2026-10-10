@php
    $tabs = [
        ['label' => 'Details', 'route' => route('charts.show', $chart), 'active' => $activeTab === 'details'],
        ['label' => 'Comments', 'route' => route('charts.comments.index', $chart), 'active' => $activeTab === 'comments'],
        ['label' => 'Settings', 'route' => null, 'active' => false],
    ];
@endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="min-w-0">
        <h1 class="truncate text-2xl font-bold tracking-tight text-foreground">{{ $chart->title }}</h1>
        <p class="mt-1 text-sm text-foreground-accent">{{ $chart->artist }}</p>
    </div>

    <div class="flex items-center gap-2">
        @if ($chart->is_private)
            <span class="inline-flex items-center gap-1 rounded-full border border-border bg-background-accent px-3 py-1 text-xs font-semibold text-foreground-accent">
                <x-lucide-lock class="h-3.5 w-3.5" /> Private
            </span>
        @endif
        <span class="inline-flex items-center rounded-full border border-border bg-background-accent px-3 py-1 text-xs font-semibold text-foreground-accent">
            {{ $chart->status->label() }}
        </span>
    </div>
</div>

{{-- Tabs --}}
<div class="mb-6 flex gap-1 border-b border-border">
    @foreach ($tabs as $tab)
        @if ($tab['route'])
            <a href="{{ $tab['route'] }}"
                class="border-b-2 px-4 py-2 text-sm font-semibold transition {{ $tab['active'] ? 'border-primary-accent text-foreground' : 'border-transparent text-foreground-accent hover:text-foreground' }}">
                {{ $tab['label'] }}
            </a>
        @else
            <span class="border-b-2 border-transparent px-4 py-2 text-sm font-semibold text-foreground-accent/60">
                {{ $tab['label'] }}
            </span>
        @endif
    @endforeach
</div>
