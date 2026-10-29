/**
 * Registers the `diffViewer` Alpine component used by the History tab.
 *
 * Each revision card lazily fetches the revision and its predecessor from the
 * diff endpoint, then renders a side-by-side (split) diff - with a unified
 * fallback - using jsdiff. The `diff` package is only downloaded on pages that
 * actually expand a revision.
 *
 * Long runs of unchanged lines are collapsed into `gap` markers, keeping a few
 * lines of context around each change. A single toggle reveals the full file.
 */

/**
 * Split a jsdiff chunk value into individual lines, dropping the single
 * trailing newline that terminates the chunk.
 *
 * Line endings are normalised to `\n` first: stored code is LF-only, but a
 * legacy revision may still contain CRLF, which would otherwise leave a stray
 * `\r` at the end of every rendered line.
 *
 * @param {string} value
 * @returns {string[]}
 */
function splitLines(value) {
    return value.replace(/\r\n?/g, '\n').replace(/\n$/, '').split('\n');
}

/**
 * Register the `diffViewer` component on the given Alpine instance.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerDiffViewer(Alpine) {
    Alpine.data('diffViewer', (url, startExpanded = false) => ({
        url,
        expanded: startExpanded,
        status: 'idle', // idle | loading | ready | error
        error: '',
        mode: 'split', // split | unified
        isInitial: false,
        current: null,
        previous: null,
        rows: [],
        displayRows: [],
        displayUnifiedRows: [],
        contextLines: 3,
        showFull: false,
        hasGaps: false,
        added: 0,
        removed: 0,
        copied: false,

        init() {
            if (this.expanded) {
                this.load();
            }
        },

        async toggle() {
            this.expanded = ! this.expanded;

            if (this.expanded && (this.status === 'idle' || this.status === 'error')) {
                await this.load();
            }
        },

        async load() {
            this.status = 'loading';
            this.error = '';

            try {
                const response = await fetch(this.url, { headers: { Accept: 'application/json' } });

                if (! response.ok) {
                    throw new Error('Failed to load the revision diff.');
                }

                const data = await response.json();
                this.current = data.current;
                this.previous = data.previous;
                this.isInitial = data.previous === null;

                await this.compute();
                this.status = 'ready';
            } catch (exception) {
                this.error = exception.message ?? 'Failed to load the revision diff.';
                this.status = 'error';
            }
        },

        /**
         * Build the paired (split) and flat (unified) row lists from the
         * previous and current code.
         */
        async compute() {
            const { diffLines } = await import('diff');

            const before = this.previous?.code ?? '';
            const after = this.current?.code ?? '';

            const parts = this.isInitial ? [{ value: after }] : diffLines(before, after);

            const rows = [];
            let leftNo = 1;
            let rightNo = 1;
            let added = 0;
            let removed = 0;

            const push = (left, right, type) => {
                rows.push({
                    type,
                    left: left === null ? null : { number: leftNo++, text: left },
                    right: right === null ? null : { number: rightNo++, text: right },
                });
            };

            let i = 0;

            while (i < parts.length) {
                const part = parts[i];

                if (! this.isInitial && part.removed && parts[i + 1]?.added) {
                    const removedLines = splitLines(part.value);
                    const addedLines = splitLines(parts[i + 1].value);
                    const length = Math.max(removedLines.length, addedLines.length);

                    for (let k = 0; k < length; k++) {
                        const left = k < removedLines.length ? removedLines[k] : null;
                        const right = k < addedLines.length ? addedLines[k] : null;

                        if (left !== null && right !== null) {
                            push(left, right, 'change');
                        } else if (left !== null) {
                            push(left, null, 'removed');
                        } else {
                            push(null, right, 'added');
                        }
                    }

                    removed += removedLines.length;
                    added += addedLines.length;
                    i += 2;
                } else if (part.added) {
                    const addedLines = splitLines(part.value);
                    addedLines.forEach((line) => push(null, line, 'added'));
                    added += addedLines.length;
                    i++;
                } else if (part.removed) {
                    const removedLines = splitLines(part.value);
                    removedLines.forEach((line) => push(line, null, 'removed'));
                    removed += removedLines.length;
                    i++;
                } else {
                    splitLines(part.value).forEach((line) => push(line, line, 'same'));
                    i++;
                }
            }

            this.rows = rows;
            this.added = added;
            this.removed = removed;
            this.applyCollapse();
        },

        /**
         * Recompute the visible rows from the full row list, honouring the
         * current `showFull` state.
         */
        applyCollapse() {
            const collapsed = this.collapse(this.rows);

            this.hasGaps = collapsed.length < this.rows.length;
            this.displayRows = this.showFull ? this.rows : collapsed;
            this.displayUnifiedRows = this.buildUnified(this.displayRows);
        },

        /**
         * Collapse long runs of unchanged lines into `gap` markers, keeping
         * `contextLines` unchanged lines around every change. The original
         * upload (no predecessor) is always shown in full.
         *
         * @param {Array} rows
         * @returns {Array}
         */
        collapse(rows) {
            if (this.isInitial) {
                return rows;
            }

            const keep = new Set();

            rows.forEach((row, index) => {
                if (row.type === 'same') {
                    return;
                }

                for (let offset = -this.contextLines; offset <= this.contextLines; offset++) {
                    const target = index + offset;

                    if (target >= 0 && target < rows.length) {
                        keep.add(target);
                    }
                }
            });

            if (keep.size === 0) {
                return rows;
            }

            const collapsed = [];
            let gap = 0;

            rows.forEach((row, index) => {
                if (keep.has(index)) {
                    if (gap > 0) {
                        collapsed.push({ type: 'gap', count: gap });
                        gap = 0;
                    }

                    collapsed.push(row);
                } else {
                    gap++;
                }
            });

            if (gap > 0) {
                collapsed.push({ type: 'gap', count: gap });
            }

            return collapsed;
        },

        /**
         * Toggle between the collapsed (changes + context) and full views.
         */
        toggleFull() {
            this.showFull = ! this.showFull;
            this.applyCollapse();
        },

        /**
         * Flatten paired rows into a single-column unified list.
         *
         * @param {Array} rows
         */
        buildUnified(rows) {
            const unified = [];

            for (const row of rows) {
                if (row.type === 'gap') {
                    unified.push({ type: 'gap', count: row.count });
                } else if (row.type === 'same') {
                    unified.push({ type: 'same', number: row.right.number, text: row.right.text });
                } else if (row.type === 'change') {
                    unified.push({ type: 'removed', number: row.left.number, text: row.left.text });
                    unified.push({ type: 'added', number: row.right.number, text: row.right.text });
                } else if (row.type === 'removed') {
                    unified.push({ type: 'removed', number: row.left.number, text: row.left.text });
                } else {
                    unified.push({ type: 'added', number: row.right.number, text: row.right.text });
                }
            }

            return unified;
        },

        /**
         * Background tint for a split cell.
         *
         * @param {string} type
         * @param {'left'|'right'} side
         */
        cellClass(type, side) {
            const sideColor = side === 'left'
                ? (type === 'removed' || type === 'change')
                : (type === 'added' || type === 'change');

            if (sideColor) {
                return side === 'left' ? 'bg-destructive/15' : 'bg-emerald-500/15';
            }

            const present = side === 'left'
                ? ! (type === 'added')
                : ! (type === 'removed');

            return present ? '' : 'bg-background-accent/30';
        },

        async copy() {
            try {
                await navigator.clipboard.writeText(this.current?.code ?? '');
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            } catch (exception) {
                // Clipboard access can be blocked; fail silently.
            }
        },
    }));
}
