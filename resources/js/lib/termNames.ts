import { getJson } from '@/lib/http';

/**
 * Every published term name, fetched once per visit and shared by all the
 * term pickers on the page.
 */
let request: Promise<string[]> | null = null;

export function loadTermNames(): Promise<string[]> {
    request ??= getJson<string[]>('/ajax/autocomplete').catch(
        (error: unknown) => {
            // Let the next picker that opens try again.
            request = null;
            throw error;
        },
    );

    return request;
}

/**
 * Names matching what was typed: those starting with it first, then those
 * containing it, both alphabetically.
 */
export function filterTermNames(
    names: string[],
    query: string,
    limit = 50,
): string[] {
    const needle = query.trim().toLowerCase();

    if (!needle) {
        return names.slice(0, limit);
    }

    const starts: string[] = [];
    const contains: string[] = [];

    for (const name of names) {
        const lower = name.toLowerCase();

        if (lower.startsWith(needle)) {
            starts.push(name);
        } else if (lower.includes(needle)) {
            contains.push(name);
        }
    }

    return [...starts, ...contains].slice(0, limit);
}
