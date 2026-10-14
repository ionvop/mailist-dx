/**
 * Localizes absolute timestamps to the visitor's timezone.
 *
 * Server-rendered datetimes are always UTC. Each `<time data-local-time>`
 * element carries the UTC value in its `datetime` attribute and a UTC tooltip
 * in `title`. This module rewrites the tooltip using the browser's own
 * timezone, so the absolute time is shown in the visitor's local time.
 *
 * The relative text (e.g. "2 hours ago") is timezone-independent and is left
 * untouched.
 */

const FORMATTER = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

/**
 * Rewrite the tooltip of every local-time element to the visitor's timezone.
 */
function applyLocalTime() {
    document.querySelectorAll('time[data-local-time]').forEach((element) => {
        const iso = element.getAttribute('datetime');

        if (!iso) {
            return;
        }

        const date = new Date(iso);

        if (Number.isNaN(date.getTime())) {
            return;
        }

        element.setAttribute('title', FORMATTER.format(date));
    });
}

/**
 * Localize all timestamps on the page, and re-scan once Alpine has finished
 * initializing in case components render additional timestamps.
 */
export function registerLocalTime() {
    applyLocalTime();

    document.addEventListener('alpine:initialized', applyLocalTime);
}
