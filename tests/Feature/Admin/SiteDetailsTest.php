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

    /**
     * Pictures already set on the site, with their files on the fake disk.
     */
    private function setPictures(string ...$fields): void
    {
        $paths = [];

        foreach ($fields as $field) {
            Storage::disk('uploads')->put("site/{$field}.png", 'png');
            $paths[$field] = "/uploads/site/{$field}.png";
        }

        Site::query()->sole()->update($paths);
    }

    public function test_an_administrator_uploads_the_idgi_pictures(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details([
                'idgi_rest' => UploadedFile::fake()->image('rest.png', 480, 324),
                'idgi_hover' => UploadedFile::fake()->image('hover.png', 480, 324),
                'idgi_thanks' => UploadedFile::fake()->image('thanks.webp', 480, 324),
            ]))
            ->assertRedirect('/site')
            ->assertSessionHasNoErrors();

        $site = Site::query()->sole();

        foreach ([$site->idgi_rest, $site->idgi_hover, $site->idgi_thanks] as $path) {
            $this->assertNotNull($path);
            $this->assertStringStartsWith('/uploads/site/', $path);
            Storage::disk('uploads')->assertExists('site/'.basename($path));
        }

        $this->get('/site')->assertInertia(fn (Assert $page) => $page
            ->where('site.idgi.rest', $site->idgi_rest)
            ->where('site.idgi.thanks', $site->idgi_thanks));
    }

    public function test_an_svg_picture_is_refused(): void
    {
        $svg = UploadedFile::fake()->createWithContent('rest.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['idgi_rest' => $svg]))
            ->assertSessionHasErrors(['idgi_rest' => 'The picture must be a PNG, JPEG, GIF or WebP image.']);

        $this->assertNull(Site::query()->sole()->idgi_rest);
    }

    public function test_a_picture_over_2_mb_is_refused(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['idgi_rest' => UploadedFile::fake()->image('rest.png')->size(3000)]))
            ->assertSessionHasErrors(['idgi_rest' => 'The picture must be no larger than 2 MB.']);

        $this->assertNull(Site::query()->sole()->idgi_rest);
    }

    public function test_a_thanks_picture_needs_a_resting_one(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['idgi_thanks' => UploadedFile::fake()->image('thanks.png')]))
            ->assertSessionHasErrors(['idgi_thanks' => 'Add the picture at rest first. The hover and thanks pictures are used alongside it.']);

        $this->assertNull(Site::query()->sole()->idgi_thanks);
        $this->assertSame([], Storage::disk('uploads')->allFiles('site'));
    }

    public function test_a_hover_picture_cannot_replace_a_resting_one_being_removed(): void
    {
        $this->setPictures('idgi_rest');

        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['remove_idgi_rest' => true, 'idgi_hover' => UploadedFile::fake()->image('hover.png')]))
            ->assertSessionHasErrors('idgi_hover');

        $this->assertSame('/uploads/site/idgi_rest.png', Site::query()->sole()->idgi_rest);
    }

    public function test_removing_the_hover_picture_leaves_the_others(): void
    {
        $this->setPictures('idgi_rest', 'idgi_hover', 'idgi_thanks');

        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['remove_idgi_hover' => true]))
            ->assertSessionHasNoErrors();

        $site = Site::query()->sole();
        $this->assertNull($site->idgi_hover);
        $this->assertSame('/uploads/site/idgi_rest.png', $site->idgi_rest);
        $this->assertSame('/uploads/site/idgi_thanks.png', $site->idgi_thanks);
        Storage::disk('uploads')->assertMissing('site/idgi_hover.png');
    }

    public function test_removing_the_resting_picture_removes_all_three(): void
    {
        $this->setPictures('idgi_rest', 'idgi_hover', 'idgi_thanks');

        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['remove_idgi_rest' => true]))
            ->assertSessionHasNoErrors();

        $site = Site::query()->sole();
        $this->assertNull($site->idgi_rest);
        $this->assertNull($site->idgi_hover);
        $this->assertNull($site->idgi_thanks);
        $this->assertSame([], Storage::disk('uploads')->allFiles('site'));
    }

    public function test_a_replaced_picture_loses_its_old_file(): void
    {
        $this->setPictures('idgi_rest');

        $this->actingAs(User::factory()->admin()->create())
            ->put('/site', $this->details(['idgi_rest' => UploadedFile::fake()->image('new.png')]))
            ->assertSessionHasNoErrors();

        $site = Site::query()->sole();
        $this->assertNotSame('/uploads/site/idgi_rest.png', $site->idgi_rest);
        Storage::disk('uploads')->assertMissing('site/idgi_rest.png');
        Storage::disk('uploads')->assertExists('site/'.basename((string) $site->idgi_rest));
    }

    public function test_someone_who_is_not_an_administrator_cannot_change_the_pictures(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/site', $this->details(['idgi_rest' => UploadedFile::fake()->image('rest.png')]))
            ->assertForbidden();

        $this->assertNull(Site::query()->sole()->idgi_rest);
        $this->assertSame([], Storage::disk('uploads')->allFiles('site'));
    }
}
