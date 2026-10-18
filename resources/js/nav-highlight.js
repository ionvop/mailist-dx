/**
 * Bottom navigation (mobile): remember the last highlighted item.
 *
 * The active state is computed server-side per request, so pages that don't
 * match any nav link (chart details, settings, ...) leave nothing highlighted.
 * This script stores the last matched item in sessionStorage and re-applies
 * its highlight on such pages.
 */
export function registerNavHighlight() {
    const STORAGE_KEY = 'last-active-nav';

    const items = Array.from(document.querySelectorAll('[data-nav-item]'));

    if (items.length === 0) {
        return;
    }

    const activeItem = items.find((item) => item.getAttribute('aria-current') === 'page');

    if (activeItem) {
        try {
            sessionStorage.setItem(STORAGE_KEY, activeItem.dataset.navItem);
        } catch {
            // sessionStorage unavailable (private mode etc.) - nothing to persist.
        }

        return;
    }

    let lastKey = null;

    try {
        lastKey = sessionStorage.getItem(STORAGE_KEY);
    } catch {
        // Ignore storage errors.
    }

    if (!lastKey) {
        return;
    }

    const lastItem = items.find((item) => item.dataset.navItem === lastKey);

    if (!lastItem) {
        return;
    }

    const isCircle = lastItem.dataset.navVariant === 'circle';

    lastItem.classList.remove(
        ...(isCircle
            ? ['bg-background', 'text-foreground-accent', 'ring-1', 'ring-border', 'hover:text-foreground']
            : ['text-foreground-accent', 'hover:bg-accent', 'hover:text-foreground']),
    );
    lastItem.classList.add(
        ...(isCircle
            ? ['bg-primary-gradient', 'text-primary-foreground', 'shadow-lg', 'shadow-primary/20']
            : ['bg-primary/20', 'text-foreground']),
    );

    const icon = lastItem.querySelector('svg');

    if (icon && isCircle) {
        icon.classList.remove('text-primary-accent');
        icon.classList.add('text-primary-foreground');
    }

    lastItem.setAttribute('aria-current', 'page');
}
