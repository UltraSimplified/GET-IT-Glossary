<?php

namespace App\Themes;

/**
 * Turns theme settings into CSS custom properties. The page head and the
 * theme editor's preview both use this, so the preview cannot drift from
 * what the site shows.
 *
 * resources/css/app.css reads the --theme-* properties and derives every
 * other colour from them; the --letter-* properties draw the letter shape.
 *
 * @phpstan-import-type Settings from ThemeSettings
 * @phpstan-import-type Palette from ThemeSettings
 * @phpstan-import-type FontChoice from ThemeSettings
 */
class ThemeStyles
{
    private const string HEXAGON = 'polygon(50% 0, 100% 25%, 100% 75%, 50% 100%, 0 75%, 0 25%)';

    /**
     * How each letter shape is drawn, and how the current letter stands out.
     *
     * @var array<string, array<string, string>>
     */
    private const array LETTER_SHAPES = [
        'plain' => [
            '--letter-clip' => 'none',
            '--letter-radius' => 'var(--radius)',
            '--letter-ratio' => '1',
            '--letter-current-fill' => 'transparent',
            '--letter-current-outline' => 'transparent',
            '--letter-current-text' => 'var(--ink)',
            '--letter-current-decoration' => 'underline',
        ],
        'square' => [
            '--letter-clip' => 'none',
            '--letter-radius' => '0',
            '--letter-ratio' => '1',
            '--letter-current-fill' => 'transparent',
            '--letter-current-outline' => 'var(--ink)',
            '--letter-current-text' => 'var(--ink)',
            '--letter-current-decoration' => 'none',
        ],
        'hexagon' => [
            '--letter-clip' => self::HEXAGON,
            '--letter-radius' => '0',
            '--letter-ratio' => '1.1547',
            '--letter-current-fill' => 'var(--primary)',
            '--letter-current-outline' => 'transparent',
            '--letter-current-text' => 'var(--primary-foreground)',
            '--letter-current-decoration' => 'none',
        ],
        'circle' => [
            '--letter-clip' => 'none',
            '--letter-radius' => '50%',
            '--letter-ratio' => '1',
            '--letter-current-fill' => 'var(--primary)',
            '--letter-current-outline' => 'transparent',
            '--letter-current-text' => 'var(--primary-foreground)',
            '--letter-current-decoration' => 'none',
        ],
    ];

    /**
     * The variables for light colours (with fonts, sizes and the letter
     * shape) and, when the theme has one, for the dark set.
     *
     * @param  Settings  $settings
     * @return array{light: array<string, string>, dark: array<string, string>|null}
     */
    public function variables(array $settings): array
    {
        $light = [
            ...$this->palette($settings['colours']['light']),
            ...$this->accents($settings['accents']),
            '--theme-font-body' => $this->fontStack($settings['fonts']['body']),
            '--theme-font-heading' => $this->fontStack($settings['fonts']['heading']),
            '--theme-base-size' => $this->number($settings['base_size'] / 16 * 100).'%',
            '--theme-radius' => $this->number($settings['radius'] / 16).'rem',
            ...self::LETTER_SHAPES[$settings['letter_shape']],
        ];

        $dark = $settings['colours']['dark'];

        return [
            'light' => $light,
            'dark' => $dark === null ? null : $this->palette($dark),
        ];
    }

    /**
     * The style sheet for the page head: light values on the root, dark
     * values when the root has the `dark` class.
     */
    public function css(ThemeDefinition $theme): string
    {
        $variables = $this->variables($theme->settings);

        $css = ':root{'.$this->declarations($variables['light']).'}';

        if ($variables['dark'] !== null) {
            $css .= ':root.dark{'.$this->declarations($variables['dark']).'}';
        }

        return $css;
    }

    /**
     * @param  Palette  $palette
     * @return array<string, string>
     */
    private function palette(array $palette): array
    {
        $variables = [];

        foreach (ThemeSettings::ROLES as $role) {
            $variables['--theme-'.str_replace('_', '-', $role)] = $palette[$role];
        }

        return $variables;
    }

    /**
     * @param  list<string>  $accents
     * @return array<string, string>
     */
    private function accents(array $accents): array
    {
        $variables = [];

        foreach ($accents as $index => $colour) {
            $variables['--theme-accent-'.($index + 1)] = $colour;
        }

        return $variables;
    }

    /**
     * A site font comes first, then its bundled fallback, then the generic
     * family the fallback belongs to.
     *
     * @param  FontChoice  $font
     */
    private function fontStack(array $font): string
    {
        $fonts = config('glossary.fonts');
        $bundled = is_array($fonts) && is_array($fonts[$font['fallback']] ?? null) ? $fonts[$font['fallback']] : [];
        $generic = ($bundled['generic'] ?? 'sans-serif') === 'serif'
            ? 'ui-serif, Georgia, serif'
            : 'ui-sans-serif, system-ui, sans-serif';

        $families = array_unique([$font['family'], $font['fallback']]);

        return implode(', ', array_map(fn (string $family): string => '"'.$family.'"', $families)).', '.$generic;
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function declarations(array $variables): string
    {
        $lines = [];

        foreach ($variables as $name => $value) {
            $lines[] = $name.':'.$value;
        }

        return implode(';', $lines);
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }
}
