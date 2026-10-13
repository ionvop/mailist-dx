/**
 * Registers the `simaiEditor` Alpine component used on the chart difficulty
 * page.
 *
 * The Shiki highlighter and the reference TextMate grammar (from MajdataEdit)
 * are loaded lazily on first use, so they are only downloaded on pages that
 * actually render a simai editor. The chart code is tokenised as a fixed
 * language - never auto-detected - so unknown syntax simply renders unstyled
 * instead of throwing.
 *
 * The component also maps the text cursor to a song time, mirroring the
 * reference editor in old/majdataedit: the chart is split into comma-separated
 * segments, each advancing the clock by one beat, and the caret snaps to the
 * segment it sits in.
 */
const LANGUAGE = 'simai';
const THEME = 'dark-plus';

/** Beat division used until a `{n}` token is seen. */
const DEFAULT_BEATS = 4;

/** BPM used when neither the chart nor the code defines one. */
const DEFAULT_BPM = 120;

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
 * Parse a simai chart into an ordered list of timing points.
 *
 * Each entry marks the start of a comma-separated segment together with the
 * song time (in seconds) at which that segment begins. This mirrors the
 * reference parser: `secondsPerBeat = 60 / bpm * 4 / beats`, advanced once per
 * segment, with `(bpm)` and `{beats}` tokens taking effect from the segment
 * they appear in. Commas inside `[...]` (durations) and `{...}` (beat
 * divisions) do not split segments.
 *
 * @param {string} code
 * @param {number} baseBpm
 * @returns {{ pos: number, time: number }[]}
 */
function parseSimaiTimings(code, baseBpm) {
    const timings = [];

    let bpm = Number.isFinite(baseBpm) && baseBpm > 0 ? baseBpm : DEFAULT_BPM;
    let beats = DEFAULT_BEATS;
    let time = 0;
    let depth = 0;
    let segmentStart = 0;

    const pushSegment = (end) => {
        const segment = code.slice(segmentStart, end);

        const bpmMatch = segment.match(/\((-?[0-9]+(?:\.[0-9]+)?)\)/);
        if (bpmMatch) {
            const value = parseFloat(bpmMatch[1]);

            if (value > 0) {
                bpm = value;
            }
        }

        const beatsMatch = segment.match(/\{(-?[0-9]+(?:\.[0-9]+)?)\}/);
        if (beatsMatch) {
            const value = parseFloat(beatsMatch[1]);

            if (value > 0) {
                beats = value;
            }
        }

        timings.push({ pos: segmentStart, time });

        time += (60 / bpm) * (4 / beats);
    };

    for (let index = 0; index < code.length; index++) {
        const char = code[index];

        if (char === '[' || char === '{') {
            depth++;
        } else if (char === ']' || char === '}') {
            if (depth > 0) {
                depth--;
            }
        } else if (char === ',' && depth === 0) {
            pushSegment(index);
            segmentStart = index + 1;
        }
    }

    pushSegment(code.length);

    return timings;
}

/**
 * Resolve the song time (seconds) at a caret offset: the first segment at or
 * after the caret, falling back to the last segment's time.
 *
 * @param {{ pos: number, time: number }[]} timings
 * @param {number} offset
 */
function caretTimeAt(timings, offset) {
    if (timings.length === 0) {
        return 0;
    }

    for (const timing of timings) {
        if (timing.pos >= offset) {
            return timing.time;
        }
    }

    return timings[timings.length - 1].time;
}

/**
 * Normalise line endings to `\n`.
 *
 * Stored chart code may use CRLF (Windows) line endings, but a `<textarea>`
 * always reports its value with LF endings. Normalising up front keeps the
 * component's `code` in sync with the textarea, so caret offsets (and the
 * derived song time / line readout) line up on first load.
 *
 * @param {string} value
 */
function normalizeEol(value) {
    return value.replace(/\r\n?/g, '\n');
}

/**
 * Format a duration in seconds as `M:SS.mmm`.
 *
 * @param {number} seconds
 */
function formatTime(seconds) {
    const safe = Number.isFinite(seconds) && seconds > 0 ? seconds : 0;
    const totalMilliseconds = Math.round(safe * 1000);
    const minutes = Math.floor(totalMilliseconds / 60000);
    const wholeSeconds = Math.floor((totalMilliseconds % 60000) / 1000);
    const milliseconds = totalMilliseconds % 1000;

    return `${minutes}:${String(wholeSeconds).padStart(2, '0')}.${String(milliseconds).padStart(3, '0')}`;
}

/**
 * Register the `simaiEditor` component on the given Alpine instance.
 *
 * @param {{ data: (name: string, callback: (...args: any[]) => object) => void }} Alpine
 */
export function registerSimaiEditor(Alpine) {
    Alpine.data('simaiEditor', (initialCode = '', config = {}) => ({
        code: normalizeEol(initialCode),
        original: normalizeEol(initialCode),
        editing: false,

        bpm: Number(config.bpm) > 0 ? Number(config.bpm) : DEFAULT_BPM,
        offset: Number(config.offset) || 0,

        /** @type {{ pos: number, time: number }[]} */
        timings: [],
        caretTime: 0,
        caretLine: 1,

        async init() {
            await this.highlight();
        },

        get dirty() {
            return this.code !== this.original;
        },

        get lineCount() {
            return (this.code.match(/\n/g)?.length ?? 0) + 1;
        },

        get timeLabel() {
            return `${formatTime(this.caretTime + this.offset)} · L ${this.caretLine}`;
        },

        /**
         * Recompute the segment timings from the current code.
         */
        recomputeTimings() {
            this.timings = parseSimaiTimings(this.code, this.bpm);
        },

        /**
         * Update the song time and line readout from the caret position.
         */
        updateCaret() {
            const input = this.$refs.input;

            if (! input) {
                return;
            }

            const position = input.selectionStart ?? 0;

            this.caretTime = caretTimeAt(this.timings, position);
            this.caretLine = (this.code.slice(0, position).match(/\n/g)?.length ?? 0) + 1;
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
            this.recomputeTimings();

            const view = this.$refs.view;

            if (! view) {
                this.updateCaret();

                return;
            }

            const highlighter = await loadHighlighter().catch(() => null);

            if (! highlighter) {
                this.updateCaret();

                return;
            }

            view.innerHTML = highlighter.codeToHtml(this.code, {
                lang: LANGUAGE,
                theme: THEME,
                structure: 'inline',
            });

            this.updateCaret();
        },
    }));
}
