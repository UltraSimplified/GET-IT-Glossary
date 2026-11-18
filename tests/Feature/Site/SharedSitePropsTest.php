<?php

namespace Tests\Feature\Site;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SharedSitePropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_show_the_site_name_and_tagline(): void
    {
        Site::query()->sole()->update([
            'name' => 'Generation R',
            'tagline' => 'Understanding health research terms',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('name', 'Generation R')
                ->where('glossary.title', 'Generation R')
                ->where('glossary.tagline', 'Understanding health research terms'));
    }

    public function test_the_site_logo_comes_first(): void
    {
        Site::query()->sole()->update(['logo' => '/uploads/site/ours.png', 'theme_key' => 'classic']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('glossary.logo', '/uploads/site/ours.png'));
    }

    public function test_without_a_site_logo_the_theme_logo_shows(): void
    {
        Site::query()->sole()->update(['logo' => null, 'theme_key' => 'aim']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('glossary.logo', '/assets/img/aimlogo.png'));
    }

    public function test_the_theme_switches_are_shared(): void
    {
        Site::query()->sole()->update(['theme_key' => 'aim']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('theme.key', 'aim')
            ->where('theme.letter_shape', 'hexagon')
            ->where('theme.home_alphabet', 'honeycomb')
            ->where('theme.definition_style', 'plain')
            ->where('theme.letter_colours', 'single')
            ->where('theme.has_dark', true));

        Site::query()->sole()->update(['theme_key' => 'classic']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('theme.key', 'classic')
            ->where('theme.letter_shape', 'square')
            ->where('theme.home_alphabet', 'row')
            ->where('theme.definition_style', 'italic-dash')
            ->where('theme.has_dark', false));
    }

    public function test_without_a_resting_picture_the_bubbles_show(): void
    {
        Site::query()->sole()->update(['idgi_rest' => null, 'idgi_thanks' => '/uploads/site/thanks.png']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('glossary.idgi', null));
    }

    public function test_a_resting_picture_stands_in_for_the_others(): void
    {
        Site::query()->sole()->update(['idgi_rest' => '/uploads/site/rest.png', 'idgi_hover' => null, 'idgi_thanks' => null]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('glossary.idgi', [
            'rest' => '/uploads/site/rest.png',
            'hover' => '/uploads/site/rest.png',
            'thanks' => '/uploads/site/rest.png',
        ]));
    }

    public function test_all_three_pictures_are_shared(): void
    {
        Site::query()->sole()->update([
            'idgi_rest' => '/uploads/site/rest.png',
            'idgi_hover' => '/uploads/site/hover.png',
            'idgi_thanks' => '/uploads/site/thanks.png',
        ]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('glossary.idgi', [
            'rest' => '/uploads/site/rest.png',
            'hover' => '/uploads/site/hover.png',
            'thanks' => '/uploads/site/thanks.png',
        ]));
    }

    public function test_the_widget_is_titled_with_the_site_name(): void
    {
        Site::query()->sole()->update(['name' => 'Generation R']);

        $this->get('/widget')->assertOk()->assertSee('<title>Generation R</title>', false);
    }
}
