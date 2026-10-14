@props([
    'improvement',
    'chart',
    'difficulty',
    'canEdit' => false,
    'revisionOptions' => null,
])

@php
    $isAuthor = $improvement->user_id === $currentUser?->id;
@endphp

<article
    x-data="{ mode: null }"
    class="overflow-hidden rounded-2xl border border-border bg-background-accent/20"
>
    <div class="flex flex-wrap items-start justify-between gap-3 p-4">
        <div class="flex items-start gap-3">
            <x-avatar :user="$improvement->user" size="sm" />

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-username :user="$improvement->user" class="text-sm font-semibold" />

                    <x-status-badge :status="$improvement->status" />
                </div>

                <p class="mt-0.5 text-xs text-foreground-accent">
                    <x-local-time :value="$improvement->created_at" />
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if ($canEdit && $improvement->status === \App\Enums\ImprovementStatus::Open)
                <button type="button" @click="mode = 'resolve'"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-300 transition hover:bg-emerald-500/20">
                    <x-lucide-check class="h-3.5 w-3.5" /> Resolve
                </button>
                <button type="button" @click="mode = 'reject'"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-1.5 text-xs font-semibold text-rose-300 transition hover:bg-rose-500/20">
                    <x-lucide-x class="h-3.5 w-3.5" /> Reject
                </button>
            @endif

            @if ($canEdit && $improvement->status !== \App\Enums\ImprovementStatus::Open)
                <x-confirm-dialog
                    :action="route('charts.difficulties.improvements.revoke', [$chart, $difficulty, $improvement])"
                    method="PATCH"
                    variant="primary"
                    title="Revoke this resolution?"
                    description="This reopens the suggestion and clears the remark and linked revision."
                    confirm-label="Revoke resolution"
                >
                    <x-slot:trigger>
                        <button type="button" @click="open = true"
                            class="inline-flex items-center gap-1 text-xs font-medium text-foreground-accent transition hover:text-foreground">
                            <x-lucide-undo-2 class="h-3.5 w-3.5" /> Revoke
                        </button>
                    </x-slot:trigger>
                </x-confirm-dialog>
            @endif

            @if ($isAuthor)
                <x-confirm-dialog
                    :action="route('charts.difficulties.improvements.destroy', [$chart, $difficulty, $improvement])"
                    title="Delete this suggestion?"
                    description="This permanently removes your improvement suggestion. This cannot be undone."
                    confirm-label="Delete suggestion"
                >
                    <x-slot:trigger>
                        <button type="button" @click="open = true"
                            class="inline-flex items-center gap-1 text-xs font-medium text-foreground-accent transition hover:text-destructive">
                            <x-lucide-trash-2 class="h-3.5 w-3.5" /> Delete
                        </button>
                    </x-slot:trigger>
                </x-confirm-dialog>
            @endif
        </div>
    </div>

    <div class="space-y-3 px-4 pb-4">
        @if ($improvement->hasSnippet())
            <div class="rounded-lg border border-border bg-background p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-foreground-accent">
                    {{ $improvement->lineLabel() }}
                </p>
                <pre class="mt-2 overflow-auto whitespace-pre-wrap break-all font-mono text-xs text-foreground">{{ $improvement->snippet }}</pre>
            </div>
        @endif

        <p class="whitespace-pre-line text-sm text-foreground">{{ $improvement->body }}</p>

        @if ($improvement->status !== \App\Enums\ImprovementStatus::Open)
            <div class="rounded-lg border border-border bg-background-accent/40 p-3 text-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-foreground-accent">
                    {{ $improvement->status === \App\Enums\ImprovementStatus::Resolved ? 'Resolved' : 'Rejected' }}
                    @if ($improvement->resolver)
                        by <x-username :user="$improvement->resolver" />
                    @endif
                </p>
                <p class="mt-1 whitespace-pre-line text-foreground">{{ $improvement->resolution_note }}</p>

                @if ($improvement->revision)
                    <p class="mt-2 text-xs text-foreground-accent">
                        Resolved in
                        <a href="{{ route('charts.difficulties.show', [$chart, $difficulty, 'history']).'?revision='.$improvement->revision->id.'#revision-'.$improvement->revision->id }}"
                            class="font-medium text-foreground transition hover:text-primary-accent">
                            revision #{{ $improvement->revision->revision_number }}
                        </a>
                        @if ($improvement->revision->user)
                            by <x-username :user="$improvement->revision->user" />
                        @endif
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Resolve / reject modal --}}
    @if ($canEdit)
        <div
            x-show="mode !== null"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            @keydown.escape.window="mode = null"
        >
            <div class="w-full max-w-lg rounded-2xl border border-border bg-background p-6 shadow-2xl" @click.outside="mode = null">
                <h2 class="text-lg font-bold text-foreground" x-text="mode === 'resolve' ? 'Resolve suggestion' : 'Reject suggestion'"></h2>
                <p class="mt-1 text-sm text-foreground-accent">
                    Add a remark explaining your decision. The author will see it on the Improvements tab.
                </p>

                <form
                    method="POST"
                    action="{{ route('charts.difficulties.improvements.update', [$chart, $difficulty, $improvement]) }}"
                    class="mt-4 space-y-3"
                >
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" :value="mode === 'resolve' ? 'resolved' : 'rejected'">

                    <x-textarea name="resolution_note" label="Remark" rows="3"
                        placeholder="Explain why this suggestion is being resolved or rejected..." required />

                    <div x-show="mode === 'resolve'">
                        <label for="revision_id_{{ $improvement->id }}" class="block text-sm font-medium text-foreground">
                            Link a history record <span class="text-foreground-accent">(optional)</span>
                        </label>
                        <select
                            name="revision_id"
                            id="revision_id_{{ $improvement->id }}"
                            class="mt-1.5 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-primary-accent focus:ring-2 focus:ring-primary-accent/40"
                        >
                            <option value="">None</option>
                            @foreach ($revisionOptions ?? [] as $revision)
                                <option value="{{ $revision->id }}">
                                    #{{ $revision->revision_number }} · {{ $revision->user?->username ?? 'Deleted user' }} · {{ $revision->created_at?->diffForHumans() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @click="mode = null"
                            class="inline-flex items-center justify-center rounded-lg border border-border bg-background-accent px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-accent">
                            Cancel
                        </button>
                        <x-button type="submit" class="w-auto">
                            <span x-text="mode === 'resolve' ? 'Resolve' : 'Reject'"></span>
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</article>
