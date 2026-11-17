<?php

namespace Tests\Feature\Site;

use App\Models\Site;
use App\Themes\ThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeHeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_head_carries_the_theme_styles_for_the_first_paint(): void
    {
        Site::query()->sole()->update(['name' => 'AIM Glossary', 'theme_key' => 'aim']);

        $this->get('/')
            ->assertOk()
            ->assertSee(':root{--theme-background:#f5f8fa;', false)
            ->assertSee(':root.dark{--theme-background:#0d1630;', false)
            ->assertSee('data-site-name="AIM Glossary"', false)
            ->assertSee('data-has-dark="true"', false)
            ->assertSee('<title>AIM Glossary</title>', false);
    }

    public function test_a_dark_preference_applies_when_the_theme_has_dark_colours(): void
    {
        Site::query()->sole()->update(['theme_key' => 'aim']);

        $this->withUnencryptedCookie('appearance', 'dark')
            ->get('/')
            ->assertSee('class="dark"', false);
    }

    public function test_a_theme_without_dark_colours_always_shows_in_light(): void
    {
        Site::query()->sole()->update(['theme_key' => 'classic']);

        $this->withUnencryptedCookie('appearance', 'dark')
            ->get('/')
            ->assertSee('data-has-dark="false"', false)
            ->assertDontSee('class="dark"', false)
            ->assertDontSee(':root.dark{', false);
    }

    public function test_the_site_font_stylesheet_is_linked_with_a_preconnect(): void
    {
        Site::query()->sole()->update(['font_stylesheet_url' => 'https://fonts.example.com/kit.css']);

        $this->get('/')
            ->assertSee('<link rel="preconnect" href="https://fonts.example.com" crossorigin>', false)
            ->assertSee('<link rel="stylesheet" href="https://fonts.example.com/kit.css">', false);
    }

    public function test_no_outside_stylesheet_without_an_address(): void
    {
        Site::query()->sole()->update(['font_stylesheet_url' => null]);

        $this->get('/')->assertDontSee('rel="preconnect"', false);
    }

    public function test_pages_load_only_the_fonts_their_theme_uses(): void
    {
        $registry = app(ThemeRegistry::class);

        $this->assertSame(['atkinson-hyperlegible-next', 'atkinson-hyperlegible-mono'], $registry->builtIn('aim')->fontAliases());
        $this->assertSame(['source-serif-4'], $registry->builtIn('classic')->fontAliases());
        $this->assertSame(['source-sans-3'], $registry->builtIn('ypags')->fontAliases());
    }

    public function test_changing_the_theme_changes_the_inertia_version(): void
    {
        Site::query()->sole()->update(['theme_key' => 'aim']);
        $before = $this->get('/', ['X-Inertia' => 'true'])->headers->get('X-Inertia-Version');

        Site::query()->sole()->update(['theme_key' => 'classic']);
        $after = $this->get('/', ['X-Inertia' => 'true'])->headers->get('X-Inertia-Version');

        $this->assertNotSame($before, $after);
    }
}
