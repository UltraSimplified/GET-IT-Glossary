<?php

namespace Tests\Feature\Site;

use App\Models\Site;
use App\Models\Theme;
use App\Support\CurrentSite;
use App\Themes\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CurrentSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_interface_language_follows_the_site(): void
    {
        Site::query()->sole()->update(['locale' => 'no']);

        $this->get('/')
            ->assertOk()
            ->assertSee('lang="no"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('glossary.locale', 'no')
                ->where('translations.browse-glossary', 'Bla ordlisten')
                // Norwegian has no translation for this yet, so English shows.
                ->where('translations.top-ten', 'Top ten terms'));

        $this->assertSame('no', app()->getLocale());
    }

    public function test_saving_the_site_clears_the_cache(): void
    {
        $current = app(CurrentSite::class);
        $this->assertSame(Site::query()->sole()->name, $current->site()->name);
        $this->assertTrue(Cache::has('site.current'));

        Site::query()->sole()->update(['name' => 'Generation R']);

        $this->assertFalse(Cache::has('site.current'));
        $this->assertSame('Generation R', $current->site()->name);
    }

    public function test_saving_or_deleting_a_theme_clears_the_cache(): void
    {
        $theme = Theme::factory()->create(['key' => 'bright', 'name' => 'Bright']);
        Site::query()->sole()->update(['theme_key' => 'bright']);

        $current = app(CurrentSite::class);
        $this->assertSame('square', $current->theme()->settings['letter_shape']);

        $theme->update(['settings' => [...ThemeSettings::defaults(), 'letter_shape' => 'circle']]);
        $this->assertSame('circle', $current->theme()->settings['letter_shape']);

        $theme->delete();
        $this->assertSame('classic', $current->theme()->key);
    }

    public function test_a_built_in_theme_is_read_from_its_file(): void
    {
        Site::query()->sole()->update(['theme_key' => 'aim']);

        $theme = app(CurrentSite::class)->theme();

        $this->assertSame('aim', $theme->key);
        $this->assertSame('hexagon', $theme->settings['letter_shape']);
        $this->assertTrue($theme->hasDark());
    }
}
