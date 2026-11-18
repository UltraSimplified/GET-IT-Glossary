/**
 * Small JSON helpers for the few requests that are not page visits
 * (search suggestions, "I don't get it", image uploads).
 */

/** A request the server refused, with its status code and its message. */
export class HttpError extends Error {
    constructor(
        message: string,
        readonly status: number,
    ) {
        super(message);
        this.name = 'HttpError';
    }
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function parse<T>(response: Response): Promise<T> {
    if (!response.ok) {
        let message = `Request failed (${response.status})`;

        try {
            const body = await response.json();
            const firstError = body?.errors
                ? Object.values(body.errors as Record<string, string[]>)[0]?.[0]
                : undefined;

            message = firstError ?? body?.message ?? message;
        } catch {
            // The response had no JSON body; keep the generic message.
        }

        throw new HttpError(message, response.status);
    }

    return (await response.json()) as T;
}

export async function getJson<T>(
    url: string,
    signal?: AbortSignal,
): Promise<T> {
    return parse<T>(
        await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal,
        }),
    );
}

export async function postJson<T>(
    url: string,
    body?: FormData | Record<string, unknown>,
): Promise<T> {
    const isForm = body instanceof FormData;

    return parse<T>(
        await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken(),
                ...(isForm ? {} : { 'Content-Type': 'application/json' }),
            },
            credentials: 'same-origin',
            body: isForm ? body : JSON.stringify(body ?? {}),
        }),
    );
}

/** The URL of a term page or search, with spaces written as "+". */
export function slugify(text: string): string {
    return encodeURIComponent(text.trim().toLowerCase().replace(/\s+/g, '+'))
        .replace(/%2B/g, '+')
        .replace(/%2F/g, '/')
        .replace(/%27/g, "'")
        .replace(/%2C/g, ',');
}
