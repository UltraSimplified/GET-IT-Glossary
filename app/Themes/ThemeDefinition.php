<?php

namespace App\Themes;

/**
 * One theme, built in or custom, with its settings normalised.
 *
 * @phpstan-import-type Settings from ThemeSettings
 */
final readonly class ThemeDefinition
{
    /**
     * @param  Settings  $settings
     */
    public function __construct(
        public string $key,
        public string $name,
        public bool $builtIn,
        public ?string $basedOn,
        public array $settings,
        public ?string $description = null,
    ) {}

    public function hasDark(): bool
    {
        return $this->settings['colours']['dark'] !== null;
    }

    public function logo(): ?string
    {
        return $this->settings['logo'];
    }

    /**
     * The @fonts aliases for the bundled families this theme uses, as body,
     * heading or fallback font.
     *
     * @return list<string>
     */
    public function fontAliases(): array
    {
        $fonts = config('glossary.fonts');
        $aliases = [];

        foreach ($this->settings['fonts'] as $font) {
            $bundled = is_array($fonts) ? ($fonts[$font['fallback']] ?? null) : null;

            if (is_array($bundled) && is_array($bundled['aliases'] ?? null)) {
                array_push($aliases, ...array_filter($bundled['aliases'], is_string(...)));
            }
        }

        return array_values(array_unique($aliases));
    }

    /**
     * What the pages need to know about the theme: the switches that
     * change markup, the accent colours and whether there is a dark set.
     *
     * @return array{key: string, name: string, letter_shape: string, home_alphabet: string, definition_style: string, letter_colours: string, accents: list<string>, has_dark: bool}
     */
    public function shared(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'letter_shape' => $this->settings['letter_shape'],
            'home_alphabet' => $this->settings['home_alphabet'],
            'definition_style' => $this->settings['definition_style'],
            'letter_colours' => $this->settings['letter_colours'],
            'accents' => $this->settings['accents'],
            'has_dark' => $this->hasDark(),
        ];
    }
}
