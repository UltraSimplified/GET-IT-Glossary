<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Themes\ThemeSettings;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'key' => Str::slug($name),
            'name' => $name,
            'based_on' => 'classic',
            'settings' => ThemeSettings::defaults(),
        ];
    }
}
