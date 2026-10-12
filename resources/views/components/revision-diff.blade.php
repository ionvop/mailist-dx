@props([
    'revision',
    'chart',
    'difficulty',
])

@php
    $diffUrl = route('charts.difficulties.revisions.diff', [$chart, $difficulty, $revision]);
@endphp

{{--
    Collapsible diff panel for a single revision. The `diffViewer` Alpine
    component (resources/js/diff.js) lazily fetches the revision and its
    predecessor and renders a side-by-side diff with a unified fallback.
--}}
<div x-data="diffViewer(@js($diffUrl))">
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border/70 px-4 py-2.5">
        <button type="button" @click="toggle()"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-foreground-accent transition hover:text-foreground">
            <x-lucide-chevron-right class="h-4 w-4 transition-transform" x-bind:class="expanded ? 'rotate-90' : ''" />
            <span x-text="expanded ? 'Hide changes' : 'View changes'"></span>
        </button>

        <div x-show="status === 'ready'" x-cloak class="flex items-center gap-3">
            <span class="font-mono text-xs font-semibold text-emerald-400" x-text="'+' + added"></span>
            <span class="font-mono text-xs font-semibold text-destructive" x-text="'-' + removed"></span>

            <button type="button" x-show="hasGaps" @click="toggleFull()"
                class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold text-foreground-accent transition hover:text-foreground">
                <x-lucide-chevrons-up-down class="h-3.5 w-3.5" />
                <span x-text="showFull ? 'Show changes only' : 'Show full file'"></span>
            </button>

            <div class="flex items-center gap-0.5 rounded-lg border border-border p-0.5">
                <button type="button" @click="mode = 'split'"
                    class="rounded-md px-2 py-0.5 text-[11px] font-semibold transition"
                    x-bind:class="mode === 'split' ? 'bg-primary-accent/15 text-foreground' : 'text-foreground-accent hover:text-foreground'">
                    Split
                </button>
                <button type="button" @click="mode = 'unified'"
                    class="rounded-md px-2 py-0.5 text-[11px] font-semibold transition"
                    x-bind:class="mode === 'unified' ? 'bg-primary-accent/15 text-foreground' : 'text-foreground-accent hover:text-foreground'">
                    Unified
                </button>
            </div>

            <button type="button" @click="copy()" title="Copy this version"
                class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold text-foreground-accent transition hover:text-foreground">
                <x-lucide-clipboard-check x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-400" />
                <x-lucide-clipboard x-show="! copied" class="h-3.5 w-3.5" />
                <span x-text="copied ? 'Copied' : 'Copy'"></span>
            </button>
        </div>
    </div>

    <div x-show="expanded" x-cloak>
        <div x-show="status === 'loading'" class="border-t border-border px-4 py-6 text-center text-sm text-foreground-accent">
            Loading changes…
        </div>

        <div x-show="status === 'error'" class="border-t border-border px-4 py-6 text-center text-sm text-destructive" x-text="error"></div>

        <div x-show="status === 'ready'" x-cloak>
            <p x-show="isInitial" class="border-t border-border bg-background-accent/40 px-4 py-2 text-xs text-foreground-accent">
                This is the original uploaded version — the code as it was when the chart was uploaded.
            </p>

            {{-- Split (side-by-side) view --}}
            <div x-show="mode === 'split'" class="overflow-x-auto border-t border-border font-mono text-xs leading-5">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-border/70 text-[10px] uppercase tracking-wide text-foreground-accent">
                            <th class="w-1/2 border-r border-border/70 px-3 py-1.5 text-left font-semibold">Previous</th>
                            <th class="w-1/2 px-3 py-1.5 text-left font-semibold">This version</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in displayRows" :key="index">
                            <tr x-bind:class="row.type === 'gap' ? 'bg-background-accent/30 text-foreground-accent/70' : 'align-top'">
                                <template x-if="row.type === 'gap'">
                                    <td colspan="2" class="px-3 py-1 text-center text-[11px] italic">
                                        <span x-text="'⋯ ' + row.count + ' unchanged ' + (row.count === 1 ? 'line' : 'lines')"></span>
                                    </td>
                                </template>
                                <template x-if="row.type !== 'gap'">
                                    <td class="w-1/2 border-r border-border/70" x-bind:class="cellClass(row.type, 'left')">
                                        <div class="flex min-h-5">
                                            <span class="w-9 shrink-0 select-none px-2 text-right text-foreground-accent/40 tabular-nums" x-text="row.left ? row.left.number : ''"></span>
                                            <pre class="whitespace-pre px-2" x-text="row.left ? row.left.text : ''"></pre>
                                        </div>
                                    </td>
                                </template>
                                <template x-if="row.type !== 'gap'">
                                    <td class="w-1/2" x-bind:class="cellClass(row.type, 'right')">
                                        <div class="flex min-h-5">
                                            <span class="w-9 shrink-0 select-none px-2 text-right text-foreground-accent/40 tabular-nums" x-text="row.right ? row.right.number : ''"></span>
                                            <pre class="whitespace-pre px-2" x-text="row.right ? row.right.text : ''"></pre>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Unified view --}}
            <div x-show="mode === 'unified'" class="overflow-x-auto border-t border-border font-mono text-xs leading-5">
                <template x-for="(row, index) in displayUnifiedRows" :key="index">
                    <div class="flex min-h-5"
                        x-bind:class="row.type === 'gap' ? 'bg-background-accent/30 text-foreground-accent/70' : (row.type === 'added' ? 'bg-emerald-500/15' : (row.type === 'removed' ? 'bg-destructive/15' : ''))">
                        <span class="w-9 shrink-0 select-none px-2 text-right text-foreground-accent/40 tabular-nums" x-text="row.type === 'gap' ? '' : row.number"></span>
                        <span class="w-4 shrink-0 select-none text-center text-foreground-accent/60"
                            x-text="row.type === 'gap' ? '' : (row.type === 'added' ? '+' : (row.type === 'removed' ? '-' : ''))"></span>
                        <span class="px-2" x-bind:class="row.type === 'gap' ? 'text-[11px] italic' : 'whitespace-pre'"
                            x-text="row.type === 'gap' ? ('⋯ ' + row.count + ' unchanged ' + (row.count === 1 ? 'line' : 'lines')) : row.text"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
