@props([
    'chart',
    'difficulty',
])

{{--
    Modal composer for a code-anchored improvement suggestion. It listens for
    the `simai-suggest` event broadcast by the code editor when the user selects
    a range of code, then opens with the selected line range and a preview of
    the snippet. The snippet itself is extracted server-side on submit; only the
    line range is sent.
--}}
<div
    x-data="{
        open: false,
        lineStart: null,
        lineEnd: null,
        preview: '',
        listen(event) {
            this.lineStart = event.detail.lineStart;
            this.lineEnd = event.detail.lineEnd;
            this.preview = event.detail.text;
            this.open = true;
        },
    }"
    @simai-suggest.window="listen($event)"
>
    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
        @keydown.escape.window="open = false"
    >
        <div
            class="w-full max-w-lg rounded-2xl border border-border bg-background p-6 shadow-2xl"
            @click.outside="open = false"
        >
            <h2 class="text-lg font-bold text-foreground">Suggest an improvement</h2>
            <p class="mt-1 text-sm text-foreground-accent">
                Point out a flaw or propose a change. Your suggestion will be posted on the Improvements tab.
            </p>

            <div class="mt-4 rounded-lg border border-border bg-background-accent/40 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-foreground-accent">
                    Selected code
                    <span class="text-foreground-accent/60" x-text="lineStart === lineEnd ? '· L ' + lineStart : '· L ' + lineStart + '-' + lineEnd"></span>
                </p>
                <pre class="mt-2 max-h-40 overflow-auto whitespace-pre-wrap break-all font-mono text-xs text-foreground" x-text="preview"></pre>
            </div>

            <form
                method="POST"
                action="{{ route('charts.difficulties.improvements.store', [$chart, $difficulty]) }}"
                class="mt-4 space-y-3"
            >
                @csrf
                <input type="hidden" name="line_start" :value="lineStart ?? ''">
                <input type="hidden" name="line_end" :value="lineEnd ?? ''">

                <x-textarea name="body" label="Your suggestion" rows="4"
                    placeholder="e.g. The note placements here don't match the song. I suggest 1,1,4,5,1,4," required />

                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="open = false"
                        class="inline-flex items-center justify-center rounded-lg border border-border bg-background-accent px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-accent">
                        Cancel
                    </button>
                    <x-button type="submit" class="w-auto">
                        <x-lucide-send class="h-4 w-4" /> Post suggestion
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
