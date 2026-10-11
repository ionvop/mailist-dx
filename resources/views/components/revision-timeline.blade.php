@props([
    'revisions',
    'chart',
    'difficulty',
    'canEdit' => false,
])

{{--
    Timeline of code snapshots for a difficulty. Each entry shows who saved the
    change and when, and (for editors) offers a confirm-guarded restore action
    plus an expandable diff against the previous snapshot.
--}}
<div {{ $attributes->class(['space-y-3']) }}>
    @foreach ($revisions as $revision)
        @php
            $isLatestRevision = $loop->first && $revisions->onFirstPage();
            $isCurrentCode = $revision->code === $difficulty->code;
            $lines = substr_count($revision->code, "\n") + 1;
            $lineLabel = number_format($lines).' '.($lines === 1 ? 'line' : 'lines');
        @endphp

        <article x-data="{ confirm: false }" class="overflow-hidden rounded-2xl border border-border bg-background-accent/20">
            <div class="flex flex-wrap items-start justify-between gap-3 p-4">
                <div class="flex items-start gap-3">
                    <x-avatar :user="$revision->user" size="sm" />

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-foreground">
                                {{ $revision->is_original ? 'Original version' : ($revision->user?->username ?? 'Deleted user') }}
                            </span>

                            @if ($revision->is_original)
                                <span class="rounded-full border border-amber-500/40 bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-300">Original</span>
                            @endif

                            @if ($isLatestRevision)
                                <span class="rounded-full border border-primary-accent/40 bg-primary-accent/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary-accent">Latest</span>
                            @endif
                        </div>

                        <p class="mt-0.5 text-xs text-foreground-accent">
                            <time datetime="{{ $revision->created_at?->toIso8601String() }}"
                                title="{{ $revision->created_at?->toDayDateTimeString() }}">
                                {{ $revision->created_at?->diffForHumans() ?? 'Unknown time' }}
                            </time>
                            · {{ $lineLabel }}
                        </p>
                    </div>
                </div>

                @if ($canEdit && ! $isCurrentCode)
                    <button type="button" @click="confirm = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 text-xs font-semibold text-foreground-accent transition hover:border-primary-accent/50 hover:text-foreground">
                        <x-lucide-history class="h-3.5 w-3.5" /> Restore
                    </button>
                @endif
            </div>

            <x-revision-diff :revision="$revision" :chart="$chart" :difficulty="$difficulty" />

            {{-- Restore confirmation modal --}}
            @if ($canEdit && ! $isCurrentCode)
                <div x-show="confirm" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                    @keydown.escape.window="confirm = false">
                    <div class="w-full max-w-md rounded-2xl border border-border bg-background p-6 shadow-2xl" @click.outside="confirm = false">
                        <h2 class="text-lg font-bold text-foreground">Restore this version?</h2>
                        <p class="mt-1 text-sm text-foreground-accent">
                            The chart code will be reverted to this snapshot. This is recorded as a new change, so the current version
                            and all prior history are kept.
                        </p>

                        <form method="POST" action="{{ route('charts.difficulties.revisions.restore', [$chart, $difficulty, $revision]) }}"
                            class="mt-6 flex items-center justify-end gap-3">
                            @csrf

                            <button type="button" @click="confirm = false"
                                class="inline-flex items-center justify-center rounded-lg border border-border bg-background-accent px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-accent">
                                Cancel
                            </button>
                            <x-button type="submit" class="w-auto">
                                <x-lucide-history class="h-4 w-4" /> Restore
                            </x-button>
                        </form>
                    </div>
                </div>
            @endif
        </article>
    @endforeach
</div>
