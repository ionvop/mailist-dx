@php
    $canManage = $chart->isOwnedBy($currentUser ?? null);

    $tabs = [
        ['label' => 'Code', 'tab' => 'code'],
        ['label' => 'Improvements', 'tab' => 'improvements'],
        ['label' => 'Comments', 'tab' => 'comments'],
        ['label' => 'History', 'tab' => 'history'],
        ['label' => 'Settings', 'tab' => 'settings', 'ownerOnly' => true],
    ];

    $color = fn ($difficulty) => $difficulty->difficulty_color
        ?: 'var(--color-difficulty-'.$difficulty->difficulty->value.')';
@endphp

<div class="mb-6">
    <a href="{{ route('charts.show', $chart) }}"
        class="mb-4 inline-flex items-center gap-1 text-sm text-foreground-accent transition hover:text-foreground">
        <x-lucide-arrow-left class="h-4 w-4" /> {{ $chart->title }}
    </a>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3" style="--difficulty-color: {{ $color($difficulty) }}">
            <span class="h-8 w-1.5 rounded-full bg-[var(--difficulty-color)]"></span>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ $difficulty->displayName() }}</h1>
                <p class="mt-1 text-sm text-foreground-accent">
                    @if ($difficulty->difficulty_value)
                        Lv.{{ $difficulty->difficulty_value }}
                    @endif
                    @if ($difficulty->charter)
                        · Chart by {{ $difficulty->charter }}
                    @endif
                </p>
            </div>
        </div>

        @if ($chart->difficulties->count() > 1)
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ($chart->difficulties as $option)
                    <a href="{{ route('charts.difficulties.show', [$chart, $option]) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs font-semibold transition {{ $option->id === $difficulty->id ? 'border-primary-accent bg-primary-accent/10 text-foreground' : 'border-border text-foreground-accent hover:text-foreground' }}"
                        style="--difficulty-color: {{ $color($option) }}">
                        <span class="inline-block h-2 w-2 rounded-full bg-[var(--difficulty-color)]"></span>
                        {{ $option->displayName() }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="mt-4 flex gap-1 overflow-x-auto border-b border-border">
        @foreach ($tabs as $item)
            @if (($item['ownerOnly'] ?? false) && ! $canManage)
                <span class="border-b-2 border-transparent px-4 py-2 text-sm font-semibold text-foreground-accent/60">
                    {{ $item['label'] }}
                </span>
            @else
                <a href="{{ route('charts.difficulties.show', [$chart, $difficulty, $item['tab']]) }}"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition {{ $activeTab === $item['tab'] ? 'border-primary-accent text-foreground' : 'border-transparent text-foreground-accent hover:text-foreground' }}">
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>
