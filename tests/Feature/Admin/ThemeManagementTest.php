<?php

namespace Tests\Feature\Admin;

use App\Models\Site;
use App\Models\Theme;
use App\Models\User;
use App\Themes\ThemeRegistry;
use App\Themes\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ThemeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function settings(array $changes = []): array
    {
        return array_replace(app(ThemeRegistry::class)->builtIn('ypags')->settings, $changes);
    }

    public function test_the_themes_screen_lists_built_ins_and_custom_themes_with_the_active_one_marked(): void
    {
        Site::query()->sole()->update(['theme_key' => 'aim']);
        Theme::factory()->create(['key' => 'bright', 'name' => 'Bright']);

        $this->actingAs($this->admin())
            ->get('/themes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/themes/Index')
                ->has('themes', 4)
                ->where('themes.0.key', 'classic')
                ->where('themes.0.built_in', true)
                ->where('themes.1.key', 'aim')
                ->where('themes.1.active', true)
                ->where('themes.2.key', 'ypags')
                ->where('themes.3.key', 'bright')
                ->where('themes.3.built_in', false)
                ->where('themes.3.active', false));
    }

    public function test_copying_a_built_in_makes_a_custom_theme_with_its_settings(): void
    {
        $this->actingAs($this->admin())
            ->post('/themes', ['from' => 'ypags', 'name' => 'Generation R bright'])
            ->assertRedirect('/themes/generation-r-bright/edit');

        $copy = Theme::query()->where('key', 'generation-r-bright')->sole();
        $this->assertSame('Generation R bright', $copy->name);
        $this->assertSame('ypags', $copy->based_on);
        $this->assertSame(app(ThemeRegistry::class)->builtIn('ypags')->settings, $copy->definition()->settings);
    }

    public function test_a_copy_never_takes_a_built_in_key(): void
    {
        $this->actingAs($this->admin())->post('/themes', ['from' => 'aim', 'name' => 'Classic']);

        $this->assertTrue(Theme::query()->where('key', 'classic-2')->exists());
    }

    public function test_built_in_themes_cannot_be_edited_or_deleted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/themes/aim/edit')->assertForbidden();
        $this->actingAs($admin)->put('/themes/aim', ['name' => 'Changed', 'settings' => $this->settings()])->assertForbidden();
        $this->actingAs($admin)->delete('/themes/aim')->assertForbidden();
    }

    public function test_an_administrator_edits_a_custom_theme(): void
    {
        Theme::factory()->create(['key' => 'bright', 'name' => 'Bright']);

        $this->actingAs($this->admin())
            ->get('/themes/bright/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/themes/Edit')
                ->where('editing.key', 'bright')
                ->where('theme.key', 'classic')
                ->where('fonts.bundled', ['Atkinson Hyperlegible Next', 'Source Sans 3', 'Source Serif 4']));

        $this->actingAs($this->admin())
            ->put('/themes/bright', ['name' => 'Brighter', 'settings' => $this->settings(['letter_shape' => 'circle'])])
            ->assertRedirect('/themes/bright/edit')
            ->assertSessionHasNoErrors();

        $theme = Theme::query()->where('key', 'bright')->sole();
        $this->assertSame('Brighter', $theme->name);
        $this->assertSame('circle', $theme->definition()->settings['letter_shape']);
    }

    public function test_invalid_settings_are_refused_beside_their_fields(): void
    {
        Theme::factory()->create(['key' => 'bright']);

        $colours = $this->settings()['colours'];
        $colours['light']['link'] = 'pink';

        $this->actingAs($this->admin())
            ->put('/themes/bright', ['name' => 'Bright', 'settings' => $this->settings([
                'colours' => $colours,
                'letter_shape' => 'square',
                'home_alphabet' => 'honeycomb',
            ])])
            ->assertSessionHasErrors([
                'settings.colours.light.link' => 'The link colour must be a colour written like #1a2b3c.',
                'settings.home_alphabet' => 'The honeycomb needs hexagon or circle letters.',
            ]);
    }

    public function test_removing_the_dark_set_keeps_the_site_in_light(): void
    {
        $theme = Theme::factory()->create(['key' => 'bright', 'settings' => app(ThemeRegistry::class)->builtIn('aim')->settings]);
        Site::query()->sole()->update(['theme_key' => 'bright']);

        $colours = $theme->definition()->settings['colours'];
        $colours['dark'] = null;

        $this->actingAs($this->admin())
            ->put('/themes/bright', ['name' => 'Bright', 'settings' => [...$theme->definition()->settings, 'colours' => $colours]])
            ->assertSessionHasNoErrors();

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('theme.has_dark', false));
    }

    public function test_a_theme_logo_is_stored_with_the_theme(): void
    {
        Storage::fake('uploads', ['url' => '/uploads']);
        Theme::factory()->create(['key' => 'bright']);

        $this->actingAs($this->admin())
            ->put('/themes/bright', [
                'name' => 'Bright',
                'settings' => $this->settings(),
                'logo' => UploadedFile::fake()->image('logo.webp', 80, 80),
            ])
            ->assertSessionHasNoErrors();

        $logo = Theme::query()->where('key', 'bright')->sole()->definition()->logo();
        $this->assertNotNull($logo);
        $this->assertStringStartsWith('/uploads/themes/', $logo);
        Storage::disk('uploads')->assertExists('themes/'.basename($logo));
    }

    public function test_the_active_theme_cannot_be_deleted(): void
    {
        Theme::factory()->create(['key' => 'bright']);
        Site::query()->sole()->update(['theme_key' => 'bright']);

        $this->actingAs($this->admin())
            ->delete('/themes/bright')
            ->assertSessionHasErrors(['theme' => 'This is the site’s theme. Choose another theme for the site before deleting it.']);

        $this->assertTrue(Theme::query()->where('key', 'bright')->exists());
    }

    public function test_a_custom_theme_not_in_use_can_be_deleted(): void
    {
        Theme::factory()->create(['key' => 'bright']);

        $this->actingAs($this->admin())->delete('/themes/bright')->assertRedirect('/themes');

        $this->assertFalse(Theme::query()->where('key', 'bright')->exists());
    }

    public function test_switching_the_theme_shows_on_the_next_page(): void
    {
        Site::query()->sole()->update(['theme_key' => 'classic']);

        $this->actingAs($this->admin())
            ->put('/site/theme', ['theme' => 'aim'])
            ->assertSessionHasNoErrors();

        $this->get('/')
            ->assertSee(':root{--theme-background:#f5f8fa;', false)
            ->assertInertia(fn (Assert $page) => $page->where('theme.key', 'aim'));
    }

    public function test_an_unknown_theme_cannot_be_chosen(): void
    {
        $this->actingAs($this->admin())
            ->put('/site/theme', ['theme' => 'gone'])
            ->assertSessionHasErrors('theme');
    }

    public function test_someone_who_is_not_an_administrator_is_refused(): void
    {
        $user = User::factory()->create();
        Theme::factory()->create(['key' => 'bright']);
        Site::query()->sole()->update(['theme_key' => 'classic']);

        $this->actingAs($user)->get('/themes')->assertForbidden();
        $this->actingAs($user)->post('/themes', ['from' => 'aim', 'name' => 'Mine'])->assertForbidden();
        $this->actingAs($user)->get('/themes/bright/edit')->assertForbidden();
        $this->actingAs($user)->put('/themes/bright', ['name' => 'Mine', 'settings' => $this->settings()])->assertForbidden();
        $this->actingAs($user)->delete('/themes/bright')->assertForbidden();
        $this->actingAs($user)->put('/site/theme', ['theme' => 'aim'])->assertForbidden();

        $this->assertSame('classic', Site::query()->sole()->theme_key);
        $this->assertSame(1, Theme::query()->count());
    }

    public function test_the_preview_returns_the_variables_for_draft_settings(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/themes/preview', ['settings' => $this->settings(['letter_shape' => 'circle'])])
            ->assertOk()
            ->assertJsonPath('light.--theme-link', '#b33174')
            ->assertJsonPath('light.--letter-radius', '50%')
            ->assertJsonPath('dark', null);
    }

    public function test_the_preview_refuses_invalid_settings(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/themes/preview', ['settings' => $this->settings(['base_size' => 40])])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('settings.base_size');
    }

    public function test_the_preview_is_for_administrators_only(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/themes/preview', ['settings' => ThemeSettings::defaults()])
            ->assertForbidden();
    }
}
