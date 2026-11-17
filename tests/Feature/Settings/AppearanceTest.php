<?php

namespace Tests\Feature\Settings;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_appearance_setting_shows_when_the_theme_has_dark_colours(): void
    {
        Site::query()->sole()->update(['theme_key' => 'aim']);

        $this->actingAs(User::factory()->create())
            ->get('/settings/appearance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Appearance')
                ->where('theme.has_dark', true));
    }

    public function test_without_dark_colours_the_setting_redirects_to_the_profile(): void
    {
        Site::query()->sole()->update(['theme_key' => 'classic']);

        $this->actingAs(User::factory()->create())
            ->get('/settings/appearance')
            ->assertRedirect('/settings/profile');
    }
}
