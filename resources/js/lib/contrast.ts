/**
 * Colour contrast as WCAG 2 defines it, for hex colours written #rrggbb.
 */

/** Text below this ratio against its background fails WCAG AA (normal text). */
export const AA_NORMAL = 4.5;

function channel(value: number): number {
    const srgb = value / 255;

    return srgb <= 0.04045 ? srgb / 12.92 : ((srgb + 0.055) / 1.055) ** 2.4;
}

/** Relative luminance, from 0 (black) to 1 (white). */
export function luminance(hex: string): number {
    const match = /^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(hex);

    if (!match) {
        return 0;
    }

    const [r, g, b] = match
        .slice(1)
        .map((part) => channel(Number.parseInt(part, 16)));

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/** The contrast ratio between two colours, from 1 to 21. */
export function contrastRatio(first: string, second: string): number {
    const [lighter, darker] = [luminance(first), luminance(second)].sort(
        (a, b) => b - a,
    );

    return (lighter + 0.05) / (darker + 0.05);
}

const LIGHT_TEXT = '#ffffff';
const DARK_TEXT = '#1a1a1a';

/** White or near-black, whichever reads better on the background. */
export function readableTextOn(background: string): string {
    return contrastRatio(background, LIGHT_TEXT) >=
        contrastRatio(background, DARK_TEXT)
        ? LIGHT_TEXT
        : DARK_TEXT;
}
