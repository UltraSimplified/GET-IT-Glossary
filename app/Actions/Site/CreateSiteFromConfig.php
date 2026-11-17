<?php

namespace App\Actions\Site;

use Illuminate\Support\Facades\DB;

/**
 * Creates the install's site record from APP_NAME, APP_LOCALE,
 * GLOSSARY_LOGO and GLOSSARY_THEME, unless one already exists.
 *
 * It runs from the migration that creates the `sites` table, so it writes
 * only the columns that migration creates, through the query builder
 * rather than the model.
 */
class CreateSiteFromConfig
{
    public const string DEFAULT_NAME = 'GET-IT Glossary';

    public const string DEFAULT_TAGLINE = 'Plain language definitions of health research terms';

    public const string DEFAULT_THEME = 'classic';

    public function handle(): void
    {
        if (DB::table('sites')->exists()) {
            return;
        }

        DB::table('sites')->insert([
            ...self::attributes(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * The first site's details. Missing settings fall back to the GET-IT
     * defaults, the English tagline and the classic theme.
     *
     * @return array{name: string, tagline: string, locale: string, logo: string|null, theme_key: string}
     */
    public static function attributes(): array
    {
        $locale = self::setting('app.locale') ?? 'en';
        $tagline = __('interface.plain-language', [], $locale);

        return [
            'name' => self::setting('app.name') ?? self::DEFAULT_NAME,
            'tagline' => is_string($tagline) && $tagline !== 'interface.plain-language' ? $tagline : self::DEFAULT_TAGLINE,
            'locale' => $locale,
            'logo' => self::setting('glossary.logo'),
            'theme_key' => self::setting('glossary.theme') ?? self::DEFAULT_THEME,
        ];
    }

    private static function setting(string $key): ?string
    {
        $value = config($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
