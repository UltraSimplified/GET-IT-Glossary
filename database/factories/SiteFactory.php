<?php

namespace Database\Factories;

use App\Actions\Site\CreateSiteFromConfig;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->word()).' Glossary',
            'tagline' => CreateSiteFromConfig::DEFAULT_TAGLINE,
            'locale' => 'en',
            'logo' => null,
            'font_stylesheet_url' => null,
            'font_families' => null,
            'theme_key' => CreateSiteFromConfig::DEFAULT_THEME,
        ];
    }
}
