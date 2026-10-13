@props([
    'name' => 'code',
    'value' => '',
    'readonly' => false,
    'bpm' => null,
    'offset' => null,
    'suggest' => false,
])

{{--
    Syntax-highlighted simai editor. The highlighted code is rendered over a
    transparent textarea by the `simaiEditor` Alpine component (see
    resources/js/simai.js). The textarea is always present so the caret can be
    placed (and text selected/copied) even without edit access; when `readonly`
    is set it is not submitted and cannot be changed. A status bar below the
    editor shows the song time at the caret.

    When `suggest` is set, selecting a range of code reveals a floating
    "Suggest improvement" button that broadcasts the selected line range so the
    improvement composer can open.
--}}
<div
    x-data="simaiEditor(@js($value), @js(['bpm' => $bpm, 'offset' => $offset, 'suggest' => $suggest]))"
    class="relative overflow-hidden rounded-xl border border-border bg-background"
>
    @if ($suggest)
        <button
            type="button"
            x-show="hasSelection"
            x-cloak
            @click="suggestSelection()"
            class="absolute right-3 top-3 z-10 inline-flex items-center gap-1.5 rounded-lg border border-primary-accent/50 bg-background/90 px-3 py-1.5 text-xs font-semibold text-foreground shadow-lg backdrop-blur transition hover:bg-primary-accent/10"
        >
            <x-lucide-message-square-plus class="h-3.5 w-3.5" /> Suggest improvement
        </button>
    @endif

    <div class="simai-scroll max-h-[36rem] overflow-auto">
        <div class="simai-editor">
            <pre
                class="simai-gutter"
                aria-hidden="true"
                x-text="Array.from({ length: lineCount }, (_, i) => i + 1).join('\n')"
            ></pre>

            <div class="simai-body">
                <pre x-ref="view" class="simai-view"></pre>

                <textarea
                    x-ref="input"
                    @unless ($readonly) name="{{ $name }}" @endunless
                    x-model="code"
                    @input.debounce.200ms="highlight()"
                    @keyup="updateCaret(); updateSelection()"
                    @click="updateCaret(); updateSelection()"
                    @mouseup="updateSelection()"
                    @select="updateCaret(); updateSelection()"
                    spellcheck="false"
                    autocapitalize="off"
                    autocomplete="off"
                    wrap="off"
                    class="simai-input"
                    @if ($readonly) readonly @endif
                    {{ $attributes }}
                ></textarea>
            </div>
        </div>
    </div>

    <div class="simai-status">
        <span class="simai-status-label">Time</span>
        <span class="simai-status-value" x-text="timeLabel"></span>
    </div>
</div>
