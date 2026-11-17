<?php

namespace Tests\Unit\Themes;

use App\Themes\ThemeSettings;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    /**
     * @param  array<mixed>  $changes
     * @return list<string>
     */
    private function errorsFor(array $changes, ?array $siteFamilies = null): array
    {
        $settings = array_replace_recursive(ThemeSettings::defaults(), $changes);

        // Lists replace the default list rather than merging into it.
        if (array_key_exists('accents', $changes)) {
            $settings['accents'] = $changes['accents'];
        }

        return array_keys(ThemeSettings::validator($settings, $siteFamilies)->errors()->toArray());
    }

    public function test_the_defaults_pass(): void
    {
        $this->assertSame([], $this->errorsFor([]));
    }

    public function test_colours_must_be_six_digit_hex(): void
    {
        $validator = ThemeSettings::validator(array_replace_recursive(ThemeSettings::defaults(), [
            'colours' => ['light' => ['link' => 'blue']],
        ]));

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The link colour must be a colour written like #1a2b3c.',
            $validator->errors()->first('colours.light.link'),
        );

        $this->assertSame(['accents.0'], $this->errorsFor(['accents' => ['#abc']]));
    }

    public function test_a_dark_set_needs_every_role(): void
    {
        $dark = ThemeSettings::defaults()['colours']['light'];
        unset($dark['surface']);

        $this->assertSame(['colours.dark.surface'], $this->errorsFor(['colours' => ['dark' => $dark]]));
        $this->assertSame([], $this->errorsFor(['colours' => ['dark' => null]]));
    }

    public function test_sizes_stay_in_range(): void
    {
        $this->assertSame(['base_size'], $this->errorsFor(['base_size' => 14]));
        $this->assertSame(['base_size'], $this->errorsFor(['base_size' => 21]));
        $this->assertSame(['radius'], $this->errorsFor(['radius' => 17]));
        $this->assertSame([], $this->errorsFor(['base_size' => 20, 'radius' => 0]));
    }

    public function test_there_are_one_to_six_accents(): void
    {
        $this->assertSame(['accents'], $this->errorsFor(['accents' => []]));
        $this->assertSame(['accents'], $this->errorsFor(['accents' => array_fill(0, 7, '#123456')]));
    }

    public function test_switches_take_only_their_values(): void
    {
        $this->assertSame(['letter_shape'], $this->errorsFor(['letter_shape' => 'star']));
        $this->assertSame(['definition_style'], $this->errorsFor(['definition_style' => 'bold']));
    }

    public function test_the_honeycomb_needs_hexagon_or_circle_letters(): void
    {
        $validator = ThemeSettings::validator(array_replace(ThemeSettings::defaults(), [
            'letter_shape' => 'square',
            'home_alphabet' => 'honeycomb',
        ]));

        $this->assertSame('The honeycomb needs hexagon or circle letters.', $validator->errors()->first('home_alphabet'));

        $this->assertSame([], $this->errorsFor(['letter_shape' => 'circle', 'home_alphabet' => 'honeycomb']));
        $this->assertSame([], $this->errorsFor(['letter_shape' => 'hexagon', 'home_alphabet' => 'honeycomb']));
    }

    public function test_cycling_letter_colours_needs_two_accents(): void
    {
        $this->assertSame(['letter_colours'], $this->errorsFor(['letter_colours' => 'cycle', 'accents' => ['#123456']]));
        $this->assertSame([], $this->errorsFor(['letter_colours' => 'cycle', 'accents' => ['#123456', '#654321']]));
    }

    public function test_fonts_come_from_the_offered_list(): void
    {
        $bundled = fn (string $family): array => ['fonts' => ['body' => ['family' => $family, 'source' => 'bundled', 'fallback' => 'Source Serif 4']]];
        $site = fn (string $family): array => ['fonts' => ['body' => ['family' => $family, 'source' => 'site', 'fallback' => 'Source Serif 4']]];

        $this->assertSame([], $this->errorsFor($bundled('Source Sans 3')));
        $this->assertSame(['fonts.body.family'], $this->errorsFor($bundled('Comic Sans')));

        $this->assertSame([], $this->errorsFor($site('sirba'), ['sirba']));
        $this->assertSame(['fonts.body.family'], $this->errorsFor($site('sirba'), ['other-font']));
        // Names are written into CSS, so only plain characters are allowed.
        $this->assertSame(['fonts.body.family'], $this->errorsFor($site('x"; } body { color: red')));
    }

    public function test_errors_land_under_a_prefix(): void
    {
        $data = ['settings' => array_replace(ThemeSettings::defaults(), ['letter_shape' => 'square', 'home_alphabet' => 'honeycomb'])];

        $errors = ThemeSettings::validator($data, null, 'settings.')->errors();

        $this->assertTrue($errors->has('settings.home_alphabet'));
    }

    public function test_normalising_fills_in_missing_keys(): void
    {
        $settings = ThemeSettings::normalise([
            'colours' => ['light' => ['link' => '#AA0000']],
            'letter_shape' => 'hexagon',
        ]);

        $defaults = ThemeSettings::defaults();

        $this->assertSame(ThemeSettings::VERSION, $settings['version']);
        $this->assertSame('#aa0000', $settings['colours']['light']['link']);
        $this->assertSame($defaults['colours']['light']['background'], $settings['colours']['light']['background']);
        $this->assertNull($settings['colours']['dark']);
        $this->assertSame(['#00008b'], $settings['accents']);
        $this->assertSame($defaults['fonts'], $settings['fonts']);
        $this->assertSame('hexagon', $settings['letter_shape']);
        $this->assertSame($defaults['home_alphabet'], $settings['home_alphabet']);
        $this->assertSame([], array_keys(ThemeSettings::validator($settings)->errors()->toArray()));
    }

    public function test_normalising_replaces_unsafe_values(): void
    {
        $settings = ThemeSettings::normalise([
            'colours' => ['light' => ['text' => 'red; background: url(x)']],
            'fonts' => ['body' => ['family' => '"><script>', 'source' => 'site', 'fallback' => 'Source Sans 3']],
            'base_size' => 99,
            'letter_shape' => 'square',
            'home_alphabet' => 'honeycomb',
            'letter_colours' => 'cycle',
            'accents' => ['#123456'],
        ]);

        $this->assertSame('#333333', $settings['colours']['light']['text']);
        $this->assertSame(ThemeSettings::defaults()['fonts']['body'], $settings['fonts']['body']);
        $this->assertSame(20, $settings['base_size']);
        $this->assertSame('grid', $settings['home_alphabet']);
        $this->assertSame('single', $settings['letter_colours']);
    }
}
