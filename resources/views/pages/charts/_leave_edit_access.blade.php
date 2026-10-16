{{-- Edit access --}}
<div class="space-y-6 rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
    <div>
        <h2 class="text-lg font-bold text-foreground">Edit access</h2>
        <p class="mt-1 text-sm text-foreground-accent">
            You have edit access to this chart. You can view private charts and edit its contents. Removing your
            edit access cannot be undone; the owner would need to grant it again.
        </p>
    </div>

    <div class="flex justify-end border-t border-border pt-6">
        <x-confirm-dialog
            :action="route('charts.settings.collaborators.leave', $chart)"
            title="Remove your edit access?"
            description="You will lose access to this chart. This cannot be undone."
            confirm-label="Remove my edit access"
        >
            <x-slot:trigger>
                <button type="button" @click="open = true"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background-accent px-3 py-1.5 text-xs font-semibold text-destructive transition hover:bg-destructive/10">
                    <x-lucide-x class="h-3.5 w-3.5" /> Remove my edit access
                </button>
            </x-slot:trigger>
        </x-confirm-dialog>
    </div>
</div>
