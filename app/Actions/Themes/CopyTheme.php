<?php

namespace App\Actions\Themes;

use App\Models\Theme;
use App\Themes\ThemeDefinition;
use App\Themes\ThemeRegistry;
use Illuminate\Support\Str;

/**
 * Makes a custom theme from any theme, built in or custom, with the same
 * settings and a name of its own.
 */
class CopyTheme
{
    public function handle(ThemeDefinition $source, string $name): Theme
    {
        return Theme::query()->create([
            'key' => $this->uniqueKey($name),
            'name' => $name,
            'based_on' => $source->key,
            'settings' => $source->settings,
        ]);
    }

    private function uniqueKey(string $name): string
    {
        $base = Str::slug($name) ?: 'theme';
        $taken = [
            ...ThemeRegistry::RESERVED,
            ...Theme::query()->where('key', 'like', $base.'%')->pluck('key')->all(),
        ];

        $key = $base;
        $suffix = 2;

        while (in_array($key, $taken, true)) {
            $key = $base.'-'.$suffix++;
        }

        return $key;
    }
}
