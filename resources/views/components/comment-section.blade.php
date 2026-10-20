@props([
    'comments',
    'heading' => 'Comments',
    'storeRoute',
    'storeParams' => [],
    'destroyRoute',
    'destroyParams' => [],
    'formLabel' => 'Add a comment',
    'placeholder' => null,
    'guestPrompt' => 'Log in to join the conversation.',
    'emptyTitle' => 'No comments yet',
    'emptyDescription' => null,
    'emptyIcon' => 'message-square',
    'deleteDescription' => 'This permanently removes your comment. This cannot be undone.',
    'removeDescription' => 'This permanently removes the comment and notifies its author. This cannot be undone.',
    'status' => null,
])

<div class="space-y-6">
    @if ($status)
        <x-alert variant="success">{{ $status }}</x-alert>
    @endif

    {{-- Comment form --}}
    @if ($currentUser)
        <form method="POST" action="{{ route($storeRoute, $storeParams) }}"
            class="space-y-3 rounded-2xl border border-border bg-background-accent/40 p-6">
            @csrf

            <x-textarea name="body" :label="$formLabel" rows="3"
                :placeholder="$placeholder" required />

            <div class="flex justify-end">
                <x-button type="submit" class="w-auto">
                    <x-lucide-send class="h-4 w-4" /> Post comment
                </x-button>
            </div>
        </form>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-background-accent/40 p-6">
            <p class="text-sm text-foreground-accent">{{ $guestPrompt }}</p>
            <x-button href="{{ route('login') }}" class="w-auto">
                <x-lucide-log-in class="h-4 w-4" /> Log in
            </x-button>
        </div>
    @endif

    {{-- Comment list --}}
    <div class="space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-foreground-accent">
            {{ $heading }}
            @if ($comments->isNotEmpty())
                <span class="text-foreground-accent/60">({{ $comments->count() }})</span>
            @endif
        </h2>

        @forelse ($comments as $comment)
            <x-comment-card
                :comment="$comment"
                :destroy-action="route($destroyRoute, [...$destroyParams, $comment])"
                :delete-description="$deleteDescription"
                :remove-description="$removeDescription"
            />
        @empty
            <x-placeholder :title="$emptyTitle" :description="$emptyDescription"
                :icon="$emptyIcon" height="h-32" />
        @endforelse
    </div>
</div>
