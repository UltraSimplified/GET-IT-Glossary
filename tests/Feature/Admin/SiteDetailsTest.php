<?php

namespace Tests\Feature\Admin;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SiteDetailsTest extends TestCase
{
    use RefreshDatabase;

    private const string KIT = "@font-face{font-family:\"sirba-web\";src:url(x.woff2)}\n@font-face{font-family:'Other Face';src:url(y.woff2)}\n.tk-sirba-web,body{font-family:\"sirba-web\",serif}";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('uploads', ['url' => '/uploads']);
        Http::preventStrayRequests();
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function details(array $changes = []): array
    {
        return [
            'name' => 'Generation R',
            'tagline' => 'Understanding health research terms',
            'locale' => 'en',
            'font_stylesheet_url' => '',
            ...$changes,
        ];
    }

    public function test_an_administrator_sees_the_site_details(): void
    {
        Site::query()->sole()->update(['name' => 'AIM Glossary', 'theme_key' => 'aim']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/site')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/site/Edit')
                ->where('site.name', 'AIM Glossary')
                ->where('themeLogo', '/assets/img/aimlogo.png')
                ->where('locales', [
                    ['value' => 'en', 'label' => 'English'],
                    ['value' => 'no', 'label' => 'Norsk (Norwegian)'],
                ]));
    }

    public function test_an_administrator_saves_new_details(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['locale' => 'no']))
            ->assertRedirect('/site');

        $site = Site::query()->sole();
        $this->assertSame('Generation R', $site->name);
        $this->assertSame('Understanding health research terms', $site->tagline);
        $this->assertSame('no', $site->locale);
        $this->assertNull($site->font_stylesheet_url);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('glossary.title', 'Generation R')
            ->where('glossary.tagline', 'Understanding health research terms'));
    }

    public function test_someone_who_is_not_an_administrator_is_refused(): void
    {
        $user = User::factory()->create();
        $before = Site::query()->sole()->name;

        $this->actingAs($user)->get('/site')->assertForbidden();
        $this->actingAs($user)->put('/site', $this->details())->assertForbidden();

        $this->assertSame($before, Site::query()->sole()->name);
    }

    public function test_a_name_is_required(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['name' => '']))
            ->assertSessionHasErrors(['name' => 'Please give the site a name.']);
    }

    public function test_only_languages_with_interface_text_are_accepted(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['locale' => 'fi']))
            ->assertSessionHasErrors('locale');
    }

    public function test_the_font_stylesheet_must_use_https(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['font_stylesheet_url' => 'http://fonts.example.com/kit.css']))
            ->assertSessionHasErrors(['font_stylesheet_url' => 'The font stylesheet address must use https.']);
    }

    public function test_the_font_families_are_read_from_the_stylesheet(): void
    {
        Http::fake(['fonts.example.com/*' => Http::response(self::KIT, 200, ['Content-Type' => 'text/css'])]);

        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['font_stylesheet_url' => 'https://fonts.example.com/kit.css']))
            ->assertRedirect('/site');

        $site = Site::query()->sole();
        $this->assertSame('https://fonts.example.com/kit.css', $site->font_stylesheet_url);
        $this->assertSame(['sirba-web', 'Other Face'], $site->font_families);
    }

    public function test_a_stylesheet_that_cannot_be_read_is_kept_with_a_warning(): void
    {
        Http::fake(['fonts.example.com/*' => fn () => throw new ConnectionException('Could not resolve host')]);

        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['font_stylesheet_url' => 'https://fonts.example.com/kit.css']))
            ->assertRedirect('/site')
            ->assertInertiaFlash('toast.type', 'warning');

        $site = Site::query()->sole();
        $this->assertSame('https://fonts.example.com/kit.css', $site->font_stylesheet_url);
        $this->assertNull($site->font_families);
    }

    public function test_a_logo_is_uploaded_and_shown(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['logo' => UploadedFile::fake()->image('logo.png', 120, 120)]))
            ->assertRedirect('/site');

        $logo = Site::query()->sole()->logo;
        $this->assertNotNull($logo);
        $this->assertStringStartsWith('/uploads/site/', $logo);
        Storage::disk('uploads')->assertExists('site/'.basename($logo));

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('glossary.logo', $logo));
    }

    public function test_an_svg_logo_is_refused(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['logo' => $svg]))
            ->assertSessionHasErrors(['logo' => 'The logo must be a PNG, JPEG, GIF or WebP image.']);

        $this->assertNull(Site::query()->sole()->logo);
    }

    public function test_removing_the_logo_shows_the_theme_logo(): void
    {
        Storage::disk('uploads')->put('site/old.png', 'png');
        Site::query()->sole()->update(['logo' => '/uploads/site/old.png', 'theme_key' => 'aim']);

        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['remove_logo' => true]))
            ->assertRedirect('/site');

        $this->assertNull(Site::query()->sole()->logo);
        Storage::disk('uploads')->assertMissing('site/old.png');
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('glossary.logo', '/assets/img/aimlogo.png'));
    }
}
