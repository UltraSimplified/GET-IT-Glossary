<?php

namespace App\Themes;

use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The shape of a theme's settings, shared by the built-in theme files and
 * the custom themes stored in the database: normalising on read, and the
 * one set of validation rules used when a theme is saved or previewed.
 *
 * @phpstan-type Palette array{background: string, surface: string, text: string, muted_text: string, border: string, link: string, primary: string, primary_text: string, accent: string, highlight: string, highlight_text: string}
 * @phpstan-type FontChoice array{family: string, source: 'bundled'|'site', fallback: string}
 * @phpstan-type Settings array{version: int, colours: array{light: Palette, dark: Palette|null}, accents: list<string>, fonts: array{body: FontChoice, heading: FontChoice}, base_size: int, radius: int, logo: string|null, letter_shape: string, home_alphabet: string, definition_style: string, letter_colours: string}
 */
final class ThemeSettings
{
    public const int VERSION = 1;

    /** The colour roles a theme sets; every other colour is derived from these. */
    public const array ROLES = [
        'background', 'surface', 'text', 'muted_text', 'border', 'link',
        'primary', 'primary_text', 'accent', 'highlight', 'highlight_text',
    ];

    public const array LETTER_SHAPES = ['plain', 'square', 'hexagon', 'circle'];

    public const array HOME_ALPHABETS = ['row', 'grid', 'honeycomb'];

    public const array DEFINITION_STYLES = ['plain', 'italic-dash'];

    public const array LETTER_COLOURS = ['single', 'cycle'];

    public const array FONT_SOURCES = ['bundled', 'site'];

    /** Letter shapes the honeycomb layout can be drawn with. */
    public const array HONEYCOMB_SHAPES = ['hexagon', 'circle'];

    public const int MAX_ACCENTS = 6;

    public const int MIN_BASE_SIZE = 15;

    public const int MAX_BASE_SIZE = 20;

    public const int MAX_RADIUS = 16;

    /** Six-digit hex colours only, so values are safe to write into CSS. */
    public const string HEX = '/^#[0-9a-fA-F]{6}$/';

    /** Font family names: letters, digits, spaces, hyphens and underscores. */
    public const string FAMILY = '/^[A-Za-z0-9][A-Za-z0-9 _-]{0,63}$/';

    /** @var Palette */
    private const array LIGHT = [
        'background' => '#ffffff',
        'surface' => '#ffffff',
        'text' => '#333333',
        'muted_text' => '#666666',
        'border' => '#dddddd',
        'link' => '#00008b',
        'primary' => '#00008b',
        'primary_text' => '#ffffff',
        'accent' => '#00008b',
        'highlight' => '#ffef9c',
        'highlight_text' => '#333333',
    ];

    /** @var Palette */
    private const array DARK = [
        'background' => '#0d1630',
        'surface' => '#131f43',
        'text' => '#e7edf7',
        'muted_text' => '#a4b2cd',
        'border' => '#283a6f',
        'link' => '#cfe0ff',
        'primary' => '#8ad4d9',
        'primary_text' => '#0d1630',
        'accent' => '#7fd2d7',
        'highlight' => '#f5cf6b',
        'highlight_text' => '#1b2540',
    ];

    /**
     * Settings for anything a theme leaves out.
     *
     * @return Settings
     */
    public static function defaults(): array
    {
        $font = ['family' => 'Source Serif 4', 'source' => 'bundled', 'fallback' => 'Source Serif 4'];

        return [
            'version' => self::VERSION,
            'colours' => ['light' => self::LIGHT, 'dark' => null],
            'accents' => [self::LIGHT['accent']],
            'fonts' => ['body' => $font, 'heading' => $font],
            'base_size' => 16,
            'radius' => 4,
            'logo' => null,
            'letter_shape' => 'square',
            'home_alphabet' => 'row',
            'definition_style' => 'plain',
            'letter_colours' => 'single',
        ];
    }

    /**
     * The bundled font families themes may use.
     *
     * @return list<string>
     */
    public static function bundledFamilies(): array
    {
        $fonts = config('glossary.fonts');

        return is_array($fonts) ? array_values(array_filter(array_keys($fonts), is_string(...))) : [];
    }

    /**
     * Bring stored settings up to the current shape: fill in missing keys
     * and replace values that could not be written safely into CSS.
     *
     * @param  array<mixed>  $settings
     * @return Settings
     */
    public static function normalise(array $settings): array
    {
        $defaults = self::defaults();
        $colours = is_array($settings['colours'] ?? null) ? $settings['colours'] : [];
        $light = self::palette($colours['light'] ?? null, self::LIGHT);
        $dark = is_array($colours['dark'] ?? null) ? self::palette($colours['dark'], self::DARK) : null;

        $accents = array_values(array_filter(
            is_array($settings['accents'] ?? null) ? $settings['accents'] : [],
            fn (mixed $colour): bool => self::isHex($colour),
        ));
        $accents = array_map(strtolower(...), array_slice($accents, 0, self::MAX_ACCENTS));

        $fonts = is_array($settings['fonts'] ?? null) ? $settings['fonts'] : [];

        $normalised = [
            'version' => self::VERSION,
            'colours' => ['light' => $light, 'dark' => $dark],
            'accents' => $accents === [] ? [$light['accent']] : $accents,
            'fonts' => [
                'body' => self::font($fonts['body'] ?? null, $defaults['fonts']['body']),
                'heading' => self::font($fonts['heading'] ?? null, $defaults['fonts']['heading']),
            ],
            'base_size' => self::integer($settings['base_size'] ?? null, $defaults['base_size'], self::MIN_BASE_SIZE, self::MAX_BASE_SIZE),
            'radius' => self::integer($settings['radius'] ?? null, $defaults['radius'], 0, self::MAX_RADIUS),
            'logo' => is_string($settings['logo'] ?? null) && $settings['logo'] !== '' ? $settings['logo'] : null,
            'letter_shape' => self::choice($settings['letter_shape'] ?? null, self::LETTER_SHAPES, $defaults['letter_shape']),
            'home_alphabet' => self::choice($settings['home_alphabet'] ?? null, self::HOME_ALPHABETS, $defaults['home_alphabet']),
            'definition_style' => self::choice($settings['definition_style'] ?? null, self::DEFINITION_STYLES, $defaults['definition_style']),
            'letter_colours' => self::choice($settings['letter_colours'] ?? null, self::LETTER_COLOURS, $defaults['letter_colours']),
        ];

        // Combinations the form refuses are drawn the nearest way that works.
        if ($normalised['home_alphabet'] === 'honeycomb' && ! in_array($normalised['letter_shape'], self::HONEYCOMB_SHAPES, true)) {
            $normalised['home_alphabet'] = 'grid';
        }

        if ($normalised['letter_colours'] === 'cycle' && count($normalised['accents']) < 2) {
            $normalised['letter_colours'] = 'single';
        }

        return $normalised;
    }

    /**
     * A validator for theme settings, with the settings at `$prefix` in the
     * data (for example "settings.") so errors land on the form's fields.
     *
     * `$siteFamilies` are the families a site font may name: those the
     * site's font stylesheet provides, plus any the theme already uses.
     * Null accepts any well-formed family name.
     *
     * @param  array<mixed>  $data
     * @param  list<string>|null  $siteFamilies
     */
    public static function validator(array $data, ?array $siteFamilies = null, string $prefix = ''): Validator
    {
        $validator = ValidatorFactory::make($data, self::rules($prefix), self::messages($prefix), self::attributes($prefix));

        $validator->after(function (Validator $validator) use ($prefix, $siteFamilies): void {
            $data = $validator->getData();
            $settings = $prefix === '' ? $data : data_get($data, rtrim($prefix, '.'));

            if (! is_array($settings)) {
                return;
            }

            self::checkCombinations($validator, $settings, $prefix);
            self::checkFonts($validator, $settings, $prefix, $siteFamilies);
        });

        return $validator;
    }

    /**
     * @return array<string, list<mixed>>
     */
    private static function rules(string $prefix): array
    {
        $hex = ['required', 'string', 'regex:'.self::HEX];

        $rules = [
            $prefix.'version' => ['nullable', 'integer', 'in:'.self::VERSION],
            $prefix.'colours' => ['required', 'array'],
            $prefix.'colours.light' => ['required', 'array'],
            $prefix.'colours.dark' => ['nullable', 'array'],
            $prefix.'accents' => ['required', 'array', 'min:1', 'max:'.self::MAX_ACCENTS],
            $prefix.'accents.*' => $hex,
            $prefix.'fonts' => ['required', 'array'],
            $prefix.'base_size' => ['required', 'integer', 'between:'.self::MIN_BASE_SIZE.','.self::MAX_BASE_SIZE],
            $prefix.'radius' => ['required', 'integer', 'between:0,'.self::MAX_RADIUS],
            $prefix.'logo' => ['nullable', 'string', 'max:255'],
            $prefix.'letter_shape' => ['required', Rule::in(self::LETTER_SHAPES)],
            $prefix.'home_alphabet' => ['required', Rule::in(self::HOME_ALPHABETS)],
            $prefix.'definition_style' => ['required', Rule::in(self::DEFINITION_STYLES)],
            $prefix.'letter_colours' => ['required', Rule::in(self::LETTER_COLOURS)],
        ];

        foreach (self::ROLES as $role) {
            $rules[$prefix.'colours.light.'.$role] = $hex;
            $rules[$prefix.'colours.dark.'.$role] = ['required_with:'.$prefix.'colours.dark', 'string', 'regex:'.self::HEX];
        }

        foreach (['body', 'heading'] as $slot) {
            $rules[$prefix.'fonts.'.$slot] = ['required', 'array'];
            $rules[$prefix.'fonts.'.$slot.'.family'] = ['required', 'string', 'regex:'.self::FAMILY];
            $rules[$prefix.'fonts.'.$slot.'.source'] = ['required', Rule::in(self::FONT_SOURCES)];
            $rules[$prefix.'fonts.'.$slot.'.fallback'] = ['required', Rule::in(self::bundledFamilies())];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private static function messages(string $prefix): array
    {
        return [
            $prefix.'colours.*.*.regex' => 'The :attribute must be a colour written like #1a2b3c.',
            $prefix.'accents.*.regex' => 'The :attribute must be a colour written like #1a2b3c.',
            $prefix.'fonts.*.family.regex' => 'The :attribute may only contain letters, numbers, spaces and hyphens.',
        ];
    }

    /**
     * Names for each field, so a message says which colour is wrong.
     *
     * @return array<string, string>
     */
    private static function attributes(string $prefix): array
    {
        $names = [];

        foreach (self::ROLES as $role) {
            $label = str_replace('_', ' ', $role).' colour';
            $names[$prefix.'colours.light.'.$role] = $label;
            $names[$prefix.'colours.dark.'.$role] = 'dark '.$label;
        }

        foreach (range(0, self::MAX_ACCENTS - 1) as $index) {
            $names[$prefix.'accents.'.$index] = 'accent colour '.($index + 1);
        }

        foreach (['body', 'heading'] as $slot) {
            $names[$prefix.'fonts.'.$slot.'.family'] = $slot.' font';
            $names[$prefix.'fonts.'.$slot.'.source'] = $slot.' font source';
            $names[$prefix.'fonts.'.$slot.'.fallback'] = $slot.' fallback font';
        }

        return $names + [
            $prefix.'base_size' => 'text size',
            $prefix.'radius' => 'corner radius',
            $prefix.'letter_shape' => 'letter shape',
            $prefix.'home_alphabet' => 'home page alphabet',
            $prefix.'definition_style' => 'definition style',
            $prefix.'letter_colours' => 'letter colours',
        ];
    }

    /**
     * @param  array<mixed>  $settings
     */
    private static function checkCombinations(Validator $validator, array $settings, string $prefix): void
    {
        if (($settings['home_alphabet'] ?? null) === 'honeycomb'
            && ! in_array($settings['letter_shape'] ?? null, self::HONEYCOMB_SHAPES, true)) {
            $validator->errors()->add($prefix.'home_alphabet', 'The honeycomb needs hexagon or circle letters.');
        }

        $accents = is_array($settings['accents'] ?? null) ? $settings['accents'] : [];

        if (($settings['letter_colours'] ?? null) === 'cycle' && count($accents) < 2) {
            $validator->errors()->add($prefix.'letter_colours', 'Letters can only take turns in the accent colours when there are at least two.');
        }
    }

    /**
     * @param  array<mixed>  $settings
     * @param  list<string>|null  $siteFamilies
     */
    private static function checkFonts(Validator $validator, array $settings, string $prefix, ?array $siteFamilies): void
    {
        foreach (['body', 'heading'] as $slot) {
            $font = data_get($settings, 'fonts.'.$slot);

            if (! is_array($font) || ! is_string($font['family'] ?? null)) {
                continue;
            }

            $offered = match ($font['source'] ?? null) {
                'bundled' => self::bundledFamilies(),
                'site' => $siteFamilies,
                default => null,
            };

            if ($offered !== null && ! in_array($font['family'], $offered, true)) {
                $validator->errors()->add($prefix.'fonts.'.$slot.'.family', "The {$slot} font must be one of the fonts offered.");
            }
        }
    }

    public static function isHex(mixed $value): bool
    {
        return is_string($value) && preg_match(self::HEX, $value) === 1;
    }

    /**
     * @param  Palette  $fallback
     * @return Palette
     */
    private static function palette(mixed $colours, array $fallback): array
    {
        $palette = $fallback;

        foreach (self::ROLES as $role) {
            $value = is_array($colours) ? ($colours[$role] ?? null) : null;

            if (self::isHex($value)) {
                $palette[$role] = strtolower($value);
            }
        }

        return $palette;
    }

    /**
     * @param  FontChoice  $fallback
     * @return FontChoice
     */
    private static function font(mixed $font, array $fallback): array
    {
        if (! is_array($font)) {
            return $fallback;
        }

        $bundled = self::bundledFamilies();
        $source = ($font['source'] ?? null) === 'site' ? 'site' : 'bundled';
        $family = $font['family'] ?? null;
        $fallbackFamily = in_array($font['fallback'] ?? null, $bundled, true) ? $font['fallback'] : $fallback['fallback'];

        $valid = is_string($family) && preg_match(self::FAMILY, $family) === 1
            && ($source === 'site' || in_array($family, $bundled, true));

        if (! $valid) {
            return $fallback;
        }

        return ['family' => $family, 'source' => $source, 'fallback' => $source === 'bundled' ? $family : $fallbackFamily];
    }

    /**
     * @param  list<string>  $options
     */
    private static function choice(mixed $value, array $options, string $fallback): string
    {
        return is_string($value) && in_array($value, $options, true) ? $value : $fallback;
    }

    private static function integer(mixed $value, int $fallback, int $min, int $max): int
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return max($min, min($max, (int) $value));
    }
}
