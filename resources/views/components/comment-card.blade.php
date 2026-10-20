@props([
    'comment',
    'destroyAction',
    'deleteDescription' => 'This permanently removes your comment. This cannot be undone.',
    'removeDescription' => 'This permanently removes the comment and notifies its author. This cannot be undone.',
])

@php
    $isAuthor = $comment->user_id === $currentUser?->id;
    $canRemove = ! $isAuthor && ($currentUser?->isModerator() ?? false);
@endphp

<div class="rounded-xl border border-border bg-background-accent/40 p-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <x-avatar :user="$comment->user" size="sm" />

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-username :user="$comment->user" class="text-sm font-semibold" />
                </div>

                <p class="mt-0.5 text-xs text-foreground-accent">
                    <x-local-time :value="$comment->created_at" />
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if ($isAuthor)
                <x-confirm-dialog
                    :action="$destroyAction"
                    title="Delete this comment?"
                    :description="$deleteDescription"
                    confirm-label="Delete comment"
                >
                    <x-slot:trigger>
                        <button type="button" @click="open = true"
                            class="inline-flex items-center gap-1 text-xs font-medium text-foreground-accent transition hover:text-destructive">
                            <x-lucide-trash-2 class="h-3.5 w-3.5" /> Delete
                        </button>
                    </x-slot:trigger>
                </x-confirm-dialog>
            @elseif ($canRemove)
                <x-confirm-dialog
                    :action="$destroyAction"
                    title="Remove this comment?"
                    :description="$removeDescription"
                    confirm-label="Remove comment"
                >
                    <x-slot:trigger>
                        <button type="button" @click="open = true"
                            class="inline-flex items-center gap-1 text-xs font-medium text-foreground-accent transition hover:text-destructive">
                            <x-lucide-shield class="h-3.5 w-3.5" /> Remove
                        </button>
                    </x-slot:trigger>
                </x-confirm-dialog>
            @endif
        </div>
    </div>

    <p class="mt-3 whitespace-pre-line text-sm text-foreground">{{ $comment->body }}</p>
</div>
