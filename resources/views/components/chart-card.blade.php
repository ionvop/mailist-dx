@props([
    'chart',
    'revision' => null,
])

<a href="{{ route('charts.show', $chart) }}"
    class="group relative flex flex-col rounded-2xl border border-border bg-background-accent/40 transition hover:border-primary-accent/50">
    <div class="relative aspect-square w-full overflow-hidden rounded-t-2xl bg-background">
        @if ($chart->art_path)
            <img src="{{ route('charts.files', [$chart, 'art']) }}" alt="{{ $chart->title }} cover"
                class="h-full w-full object-cover transition group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center">
                <x-lucide-image class="h-8 w-8 text-foreground-accent" />
            </div>
        @endif

        <x-status-badge :status="$chart->status" class="absolute right-2 top-2 z-10" />
    </div>

    @if ($chart->difficulties->isNotEmpty())
        <div class="z-10 mx-4 -mb-4 -mt-4 h-8">
            <x-difficulty-badge-list :difficulties="$chart->difficulties" />
        </div>
    @endif

    <div class="flex grow flex-col gap-1 rounded-b-2xl p-4 {{ $chart->difficulties->isNotEmpty() ? 'pt-6' : '' }}">
        <div class="flex items-start justify-between gap-2">
            <p class="truncate text-sm font-bold text-foreground">{{ $chart->title }}</p>
            @if ($chart->is_private)
                <x-lucide-lock class="mt-0.5 h-3.5 w-3.5 shrink-0 text-foreground-accent" />
            @endif
        </div>
        <p class="truncate text-xs text-foreground-accent">{{ $chart->artist }}</p>
        <div class="mt-1 flex items-center justify-between gap-2 text-xs text-foreground-accent">
            <div class="flex min-w-0 items-center gap-1.5">
                <x-avatar :user="$chart->uploader" size="xs" />
                <span class="truncate">{{ $chart->uploader->username }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <x-local-time :value="$chart->created_at" />
                <span class="inline-flex items-center gap-1">
                    <x-lucide-heart class="h-3.5 w-3.5" />
                    {{ $chart->likes_count }}
                </span>
            </div>
        </div>

        @if ($revision)
            @php
                $revisionDifficulty = $revision->difficulty;
                $revisionDifficultyColor = $revisionDifficulty?->difficulty_color
                    ?: 'var(--color-difficulty-'.$revisionDifficulty?->difficulty?->value.')';
            @endphp
            <div class="mt-2 flex flex-wrap items-center gap-x-1 border-t border-border pt-2 text-xs text-foreground-accent">
                @if ($revisionDifficulty)
                    <span class="inline-flex items-center gap-1.5"
                        style="--difficulty-color: {{ $revisionDifficultyColor }}"
                        title="{{ $revisionDifficulty->displayName() }}">
                        <span class="h-2 w-2 shrink-0 rounded-full bg-[var(--difficulty-color)]" aria-hidden="true"></span>
                        <span class="font-medium text-foreground">{{ $revisionDifficulty->displayName() }}</span>
                    </span>
                    <span aria-hidden="true">·</span>
                @endif
                <span class="font-medium text-foreground">revision #{{ $revision->revision_number }}</span>
                @if ($revision->user)
                    <span>by {{ $revision->user->username }}</span>
                @endif
                <span aria-hidden="true">·</span>
                <x-local-time :value="$revision->created_at" />
            </div>
        @endif
    </div>
</a>
