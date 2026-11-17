<?php

namespace App\Support;

use Locale;

/**
 * The interface languages this install has text for: the folders in lang/
 * that hold an interface.php.
 */
class SiteLocales
{
    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        $codes = [];

        foreach (glob(lang_path('*/interface.php')) ?: [] as $file) {
            $codes[] = basename(dirname($file));
        }

        sort($codes);

        return $codes;
    }

    /**
     * Each language by its own name, with the English name after it.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(function (string $code): array {
            // Without a name for the code (intl can't place it), the code itself shows.
            $own = Locale::getDisplayLanguage($code, $code) ?: $code;
            $english = Locale::getDisplayLanguage($code, 'en') ?: $code;
            $label = $own === $english ? $own : "{$own} ({$english})";

            return ['value' => $code, 'label' => mb_convert_case($label, MB_CASE_TITLE)];
        }, self::codes());
    }
}
