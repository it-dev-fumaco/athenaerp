/**
 * Lifecycle status colors (Search Results tag, Item Profile, etc.).
 * Keep in sync with config/lifecycle_status.php for Blade.
 */
export const STATUS_COLORS = {
    Active: '#22C55E',
    'For Phase Out': '#F59E0B',
    Discontinued: '#6B7280',
    'Raw Material': '#22C55E',
};

/** Alias for STATUS_COLORS (label -> hex background) */
export const STATUS_CONFIG = STATUS_COLORS;

/** Neutral gray for unknown / unmapped statuses */
export const UNKNOWN_STATUS_COLOR = '#6B7280';

/**
 * @param {string | null | undefined} status
 * @returns {{ label: string, backgroundColor: string, isKnown: boolean } | null}
 */
export function resolveLifecycleStatusDisplay(status) {
    if (status == null) {
        return null;
    }
    const trimmed = String(status).trim();
    if (trimmed === '') {
        return null;
    }
    const isKnown = Object.prototype.hasOwnProperty.call(STATUS_COLORS, trimmed);
    const backgroundColor = isKnown ? STATUS_COLORS[trimmed] : UNKNOWN_STATUS_COLOR;
    return {
        label: trimmed,
        backgroundColor,
        isKnown,
    };
}
