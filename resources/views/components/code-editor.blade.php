@props([
    'name' => 'code',
    'value' => '',
    'readonly' => false,
    'bpm' => null,
    'offset' => null,
])

{{--
    Syntax-highlighted simai editor. The highlighted code is rendered over a
    transparent textarea by the `simaiEditor` Alpine component (see
    resources/js/simai.js). The textarea is always present so the caret can be
    placed (and text selected/copied) even without edit access; when `readonly`
    is set it is not submitted and cannot be changed. A status bar below the
    editor shows the song time at the caret.
--}}
<div
    x-data="simaiEditor(@js($value), @js(['bpm' => $bpm, 'offset' => $offset]))"
    class="overflow-hidden rounded-xl border border-border bg-background"
>
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
                    @keyup="updateCaret()"
                    @click="updateCaret()"
                    @select="updateCaret()"
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
