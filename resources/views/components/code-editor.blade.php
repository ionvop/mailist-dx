@props([
    'name' => 'code',
    'value' => '',
    'readonly' => false,
])

{{--
    Syntax-highlighted simai editor. The highlighted code is rendered over a
    transparent textarea by the `simaiEditor` Alpine component (see
    resources/js/simai.js). When `readonly` is set, only the highlighted view
    is shown.
--}}
<div
    x-data="simaiEditor(@js($value))"
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

                @unless ($readonly)
                    <textarea
                        x-ref="input"
                        name="{{ $name }}"
                        x-model="code"
                        @input.debounce.200ms="highlight()"
                        spellcheck="false"
                        autocapitalize="off"
                        autocomplete="off"
                        wrap="off"
                        class="simai-input"
                        {{ $attributes }}
                    ></textarea>
                @endunless
            </div>
        </div>
    </div>
</div>
