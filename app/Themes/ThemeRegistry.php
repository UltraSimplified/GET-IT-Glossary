<?php

namespace App\Themes;

use App\Models\Theme;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

/**
 * Lists and finds themes: the built-ins in resources/themes and the
 * custom themes in the database. Built-in keys are reserved.
 */
class ThemeRegistry
{
    public const array BUILT_IN = ['classic', 'aim', 'ypags'];

    public const string FALLBACK = 'classic';

    /** Keys a custom theme cannot take: the built-ins, and words used in addresses. */
    public const array RESERVED = [...self::BUILT_IN, 'preview', 'create'];

    /** @var array<string, ThemeDefinition> */
    private array $builtIns = [];

    public function isBuiltIn(string $key): bool
    {
        return in_array($key, self::BUILT_IN, true);
    }

    /**
     * @return list<ThemeDefinition>
     */
    public function builtIns(): array
    {
        return array_map($this->builtIn(...), self::BUILT_IN);
    }

    /**
     * @return list<ThemeDefinition>
     */
    public function custom(): array
    {
        $themes = Theme::query()->orderBy('name')->get();

        return array_values($themes->map(fn (Theme $theme): ThemeDefinition => $theme->definition())->all());
    }

    /**
     * @return list<ThemeDefinition>
     */
    public function all(): array
    {
        return [...$this->builtIns(), ...$this->custom()];
    }

    public function find(string $key): ?ThemeDefinition
    {
        if ($this->isBuiltIn($key)) {
            return $this->builtIn($key);
        }

        return Theme::query()->where('key', $key)->first()?->definition();
    }

    /**
     * The theme with this key, or classic when there is none.
     */
    public function resolve(string $key): ThemeDefinition
    {
        $theme = $this->find($key);

        if ($theme === null) {
            Log::warning('The site\'s theme does not exist; using the classic theme.', ['theme' => $key]);

            return $this->builtIn(self::FALLBACK);
        }

        return $theme;
    }

    public function builtIn(string $key): ThemeDefinition
    {
        return $this->builtIns[$key] ??= $this->load($key);
    }

    private function load(string $key): ThemeDefinition
    {
        $path = resource_path("themes/{$key}.json");
        $contents = is_file($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new RuntimeException("The built-in theme file [{$path}] could not be read.");
        }

        try {
            $data = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("The built-in theme file [{$path}] is not valid JSON.", previous: $exception);
        }

        if (! is_array($data) || ! is_string($data['name'] ?? null) || ! is_array($data['settings'] ?? null)) {
            throw new RuntimeException("The built-in theme file [{$path}] needs a name and settings.");
        }

        return new ThemeDefinition(
            key: $key,
            name: $data['name'],
            builtIn: true,
            basedOn: null,
            settings: ThemeSettings::normalise($data['settings']),
            description: is_string($data['description'] ?? null) ? $data['description'] : null,
        );
    }

    /**
     * The raw settings of a built-in theme file, before normalising, so
     * tests can check the files themselves pass validation.
     *
     * @return array<mixed>
     */
    public function rawBuiltInSettings(string $key): array
    {
        $contents = file_get_contents(resource_path("themes/{$key}.json"));
        $data = json_decode($contents === false ? '' : $contents, true);

        return is_array($data) && is_array($data['settings'] ?? null) ? $data['settings'] : [];
    }
}
