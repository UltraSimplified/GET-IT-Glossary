<?php

namespace Tests\Unit\Themes;

use App\Themes\ThemeRegistry;
use App\Themes\ThemeStyles;
use Tests\TestCase;

class ThemeStylesTest extends TestCase
{
    public function test_the_aim_map_reproduces_the_previous_stylesheet(): void
    {
        $variables = app(ThemeStyles::class)->variables(app(ThemeRegistry::class)->builtIn('aim')->settings);

        // The literal values app.css held before themes, by the token they set.
        $this->assertSame([
            '--theme-background' => '#f5f8fa', // --background
            '--theme-surface' => '#ffffff', // --card, --popover
            '--theme-text' => '#1b2540', // --foreground
            '--theme-muted-text' => '#52607a', // --muted-foreground
            '--theme-border' => '#d3dde4', // --border
            '--theme-link' => '#16306b', // --ink
            '--theme-primary' => '#16306b', // --primary
            '--theme-primary-text' => '#ffffff', // --primary-foreground
            '--theme-accent' => '#0f757c', // --brand
            '--theme-highlight' => '#f5cf6b', // --highlight
            '--theme-highlight-text' => '#1b2540', // --highlight-foreground
        ], array_intersect_key($variables['light'], array_flip(array_keys($variables['dark'] ?? []))));

        $this->assertSame([
            '--theme-background' => '#0d1630',
            '--theme-surface' => '#131f43',
            '--theme-text' => '#e7edf7',
            '--theme-muted-text' => '#a4b2cd',
            '--theme-border' => '#283a6f',
            '--theme-link' => '#cfe0ff',
            '--theme-primary' => '#8ad4d9',
            '--theme-primary-text' => '#0d1630',
            '--theme-accent' => '#7fd2d7',
            '--theme-highlight' => '#f5cf6b',
            '--theme-highlight-text' => '#1b2540',
        ], $variables['dark']);

        $this->assertSame('"Atkinson Hyperlegible Next", ui-sans-serif, system-ui, sans-serif', $variables['light']['--theme-font-body']);
        $this->assertSame('0.5rem', $variables['light']['--theme-radius']);
        $this->assertSame('100%', $variables['light']['--theme-base-size']);
        $this->assertSame('polygon(50% 0, 100% 25%, 100% 75%, 50% 100%, 0 75%, 0 25%)', $variables['light']['--letter-clip']);
        $this->assertSame('var(--primary)', $variables['light']['--letter-current-fill']);
    }

    public function test_classic_has_no_dark_map(): void
    {
        $classic = app(ThemeRegistry::class)->builtIn('classic');
        $styles = app(ThemeStyles::class);

        $this->assertNull($styles->variables($classic->settings)['dark']);
        $this->assertStringNotContainsString('.dark', $styles->css($classic));
    }

    public function test_a_site_font_is_followed_by_its_fallback(): void
    {
        $variables = app(ThemeStyles::class)->variables(app(ThemeRegistry::class)->builtIn('classic')->settings);

        $this->assertSame('"sirba-web", "Source Serif 4", ui-serif, Georgia, serif', $variables['light']['--theme-font-body']);
        $this->assertSame('var(--ink)', $variables['light']['--letter-current-outline']);
    }

    public function test_the_head_css_sets_light_on_the_root_and_dark_on_the_dark_class(): void
    {
        $css = app(ThemeStyles::class)->css(app(ThemeRegistry::class)->builtIn('aim'));

        $this->assertStringStartsWith(':root{--theme-background:#f5f8fa;', $css);
        $this->assertStringContainsString(':root.dark{--theme-background:#0d1630;', $css);
        $this->assertStringNotContainsString('</', $css);
    }
}
