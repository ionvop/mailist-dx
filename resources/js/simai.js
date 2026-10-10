/**
 * Registers the `simaiEditor` Alpine component used on the chart difficulty
 * page.
 *
 * The Shiki highlighter and the reference TextMate grammar (from MajdataEdit)
 * are loaded lazily on first use, so they are only downloaded on pages that
 * actually render a simai editor. The chart code is tokenised as a fixed
 * language - never auto-detected - so unknown syntax simply renders unstyled
 * instead of throwing.
 */
const LANGUAGE = 'simai';
const THEME = 'dark-plus';

/** @type {Promise<import('shiki/core').HighlighterCore> | null} */
let highlighterPromise = null;

/**
 * Lazily create the shared Shiki highlighter.
 */
function loadHighlighter() {
    highlighterPromise ??= Promise.all([
        import('shiki/core'),
        import('shiki/engine/javascript'),
        import('shiki/themes/dark-plus.mjs'),
        import('./grammars/simai.tmLanguage.json'),
    ]).then(([{ createHighlighterCore }, { createJavaScriptRegexEngine }, { default: theme }, { default: grammar }]) =>
        createHighlighterCore({
            themes: [theme],
            langs: [grammar],
            engine: createJavaScriptRegexEngine(),
        }),
    );

    return highlighterPromise;
}

/**
 * Register the `simaiEditor` component on the given Alpine instance.
 */
export function registerSimaiEditor(Alpine) {
    Alpine.data('simaiEditor', (initialCode = '') => ({
        code: initialCode,
        original: initialCode,
        editing: false,

        async init() {
            await this.highlight();
        },

        get dirty() {
            return this.code !== this.original;
        },

        get lineCount() {
            return (this.code.match(/\n/g)?.length ?? 0) + 1;
        },

        startEditing() {
            this.editing = true;
            this.$nextTick(() => this.$refs.input?.focus());
        },

        cancel() {
            this.code = this.original;
            this.editing = false;
        },

        async highlight() {
            const view = this.$refs.view;

            if (! view) {
                return;
            }

            const highlighter = await loadHighlighter().catch(() => null);

            if (! highlighter) {
                return;
            }

            view.innerHTML = highlighter.codeToHtml(this.code, {
                lang: LANGUAGE,
                theme: THEME,
                structure: 'inline',
            });
        },
    }));
}
