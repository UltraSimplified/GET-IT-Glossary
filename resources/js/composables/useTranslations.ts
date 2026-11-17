import { usePage } from '@inertiajs/vue3';

/**
 * Interface strings from lang/{locale}/interface.php.
 *
 * `t('hi')` returns the string for the current locale, `t('index-of',
 * { letter: 'B' })` fills in :letter. An unknown key returns the key
 * itself so a missing translation is visible rather than blank.
 */
export function useTranslations() {
    const page = usePage();

    function t(key: string, replace: Record<string, string | number> = {}) {
        let line = page.props.translations?.[key] ?? key;

        for (const [name, value] of Object.entries(replace)) {
            line = line.replaceAll(`:${name}`, String(value));
        }

        return line;
    }

    return { t };
}
