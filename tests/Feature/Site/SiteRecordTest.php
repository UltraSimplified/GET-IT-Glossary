<?php

namespace Tests\Feature\Site;

use App\Actions\Site\CreateSiteFromConfig;
use App\Models\Site;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrating_creates_exactly_one_site_record(): void
    {
        $this->assertSame(1, Site::query()->count());
        $this->assertSame(0, Theme::query()->count());
    }

    public function test_the_site_record_takes_the_install_settings(): void
    {
        DB::table('sites')->delete();

        config([
            'app.name' => 'AIM Glossary',
            'app.locale' => 'no',
            'glossary.logo' => '/assets/img/aimlogo.png',
            'glossary.theme' => 'aim',
        ]);

        (new CreateSiteFromConfig)->handle();

        $site = Site::query()->sole();
        $this->assertSame('AIM Glossary', $site->name);
        $this->assertSame('no', $site->locale);
        $this->assertSame('/assets/img/aimlogo.png', $site->logo);
        $this->assertSame('aim', $site->theme_key);
        // The tagline comes from the site's own language.
        $this->assertSame('Klartekst definisjoner av helseforskningsvilkår', $site->tagline);
    }

    public function test_missing_settings_give_the_get_it_defaults(): void
    {
        DB::table('sites')->delete();

        config([
            'app.name' => null,
            'app.locale' => null,
            'glossary.logo' => null,
            'glossary.theme' => null,
        ]);

        (new CreateSiteFromConfig)->handle();

        $site = Site::query()->sole();
        $this->assertSame('GET-IT Glossary', $site->name);
        $this->assertSame('Plain language definitions of health research terms', $site->tagline);
        $this->assertSame('en', $site->locale);
        $this->assertNull($site->logo);
        $this->assertSame('classic', $site->theme_key);
    }

    public function test_running_again_leaves_an_existing_record_alone(): void
    {
        Site::query()->sole()->update(['name' => 'Generation R', 'theme_key' => 'ypags']);

        config(['app.name' => 'Something else', 'glossary.theme' => 'aim']);

        (new CreateSiteFromConfig)->handle();
        $this->artisan('migrate')->assertSuccessful();

        $site = Site::query()->sole();
        $this->assertSame('Generation R', $site->name);
        $this->assertSame('ypags', $site->theme_key);
    }
}
