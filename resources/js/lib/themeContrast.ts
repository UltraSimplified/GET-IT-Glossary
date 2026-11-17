import { AA_NORMAL, contrastRatio } from '@/lib/contrast';
import type { ColourRole, Palette } from '@/types';

/** A colour the stylesheet mixes from the roles, as well as the roles themselves. */
export type ContrastColour = ColourRole | 'accent_tint';

export type ContrastIssue = {
    text: ContrastColour;
    background: ContrastColour;
    ratio: number;
};

/** Mix `amount` (0–1) of one #rrggbb colour into another, as color-mix() in srgb does. */
export function mix(colour: string, base: string, amount: number): string {
    const channels = (hex: string) =>
        [1, 3, 5].map((start) =>
            Number.parseInt(hex.slice(start, start + 2), 16),
        );
    const [a, b] = [channels(colour), channels(base)];

    return (
        '#' +
        a
            .map((value, index) =>
                Math.round(value * amount + b[index] * (1 - amount))
                    .toString(16)
                    .padStart(2, '0'),
            )
            .join('')
    );
}

/**
 * Text and the backgrounds it sits on across the glossary. The accent tint
 * is the pale accent wash behind tags and hovered links (14% of the accent
 * mixed into the surface colour, as in resources/css/app.css).
 */
const PAIRS: [ContrastColour, ContrastColour][] = [
    ['text', 'background'],
    ['text', 'surface'],
    ['muted_text', 'background'],
    ['muted_text', 'surface'],
    ['link', 'background'],
    ['link', 'surface'],
    ['link', 'accent_tint'],
    ['primary_text', 'primary'],
    ['highlight_text', 'highlight'],
];

const HEX = /^#[0-9a-f]{6}$/i;

/** The pairs in a palette that fall below WCAG AA for normal text. */
export function contrastIssues(palette: Palette): ContrastIssue[] {
    const colour = (name: ContrastColour) =>
        name === 'accent_tint'
            ? mix(palette.accent, palette.surface, 0.14)
            : palette[name];

    return PAIRS.flatMap(([text, background]) => {
        const [foreground, behind] = [colour(text), colour(background)];

        if (!HEX.test(foreground) || !HEX.test(behind)) {
            return [];
        }

        const ratio = contrastRatio(foreground, behind);

        return ratio < AA_NORMAL ? [{ text, background, ratio }] : [];
    });
}
