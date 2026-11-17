<?php

namespace Tests\Feature\Site;

use App\Models\Theme;
use App\Themes\ThemeRegistry;
use App\Themes\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ThemeRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_three_built_ins_load_and_pass_validation(): void
    {
        $registry = app(ThemeRegistry::class);

        $this->assertSame(['classic', 'aim', 'ypags'], array_map(fn ($theme) => $theme->key, $registry->builtIns()));

        foreach (ThemeRegistry::BUILT_IN as $key) {
            $theme = $registry->builtIn($key);
            $this->assertTrue($theme->builtIn);
            $this->assertNotSame('', $theme->name);

            // The file as written, before normalising, must already be valid.
            $raw = $registry->rawBuiltInSettings($key);
            $errors = ThemeSettings::validator($raw)->errors()->toArray();
            $this->assertSame([], $errors, "The {$key} theme file is not valid.");
            $this->assertSame(ThemeSettings::normalise($raw), $theme->settings, "The {$key} theme file is not in normal form.");
        }
    }

    public function test_built_in_keys_are_reserved(): void
    {
        $registry = app(ThemeRegistry::class);

        foreach (['classic', 'aim', 'ypags'] as $key) {
            $this->assertTrue($registry->isBuiltIn($key));
        }

        $this->assertFalse($registry->isBuiltIn('generation-r-bright'));
    }

    public function test_custom_themes_are_listed_after_the_built_ins(): void
    {
        Theme::factory()->create(['key' => 'bright', 'name' => 'Bright']);

        $keys = array_map(fn ($theme) => $theme->key, app(ThemeRegistry::class)->all());

        $this->assertSame(['classic', 'aim', 'ypags', 'bright'], $keys);
        $this->assertFalse(app(ThemeRegistry::class)->find('bright')?->builtIn);
    }

    public function test_an_unknown_key_resolves_to_classic_with_a_warning(): void
    {
        Log::shouldReceive('warning')->once()->withArgs(fn (string $message, array $context) => $context === ['theme' => 'gone']);

        $theme = app(ThemeRegistry::class)->resolve('gone');

        $this->assertSame('classic', $theme->key);
    }
}
