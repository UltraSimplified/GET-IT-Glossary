<?php

namespace App\Support;

use App\Actions\Site\CreateSiteFromConfig;
use App\Models\Site;
use App\Models\Theme;
use App\Themes\ThemeDefinition;
use App\Themes\ThemeRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The install's site record and its active theme, resolved once per
 * request. The database rows are cached until a site or theme is saved
 * or deleted; built-in themes are read from their files, so updates to
 * them arrive with the code.
 */
class CurrentSite
{
    private const string CACHE_KEY = 'site.current';

    private ?Site $site = null;

    private ?ThemeDefinition $theme = null;

    public function __construct(private readonly ThemeRegistry $themes) {}

    public function site(): Site
    {
        return $this->site ??= $this->loadSite();
    }

    public function theme(): ThemeDefinition
    {
        return $this->theme ??= $this->loadTheme();
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);

        $this->site = null;
        $this->theme = null;
    }

    private function loadSite(): Site
    {
        $attributes = $this->cached()['site'];

        // Without a row (it should never be missing) the install's settings stand in.
        return $attributes === null
            ? new Site(CreateSiteFromConfig::attributes())
            : (new Site)->newFromBuilder($attributes);
    }

    private function loadTheme(): ThemeDefinition
    {
        $key = $this->site()->theme_key;

        if ($this->themes->isBuiltIn($key)) {
            return $this->themes->builtIn($key);
        }

        $attributes = $this->cached()['theme'];

        return $attributes === null
            ? $this->themes->builtIn(ThemeRegistry::FALLBACK)
            : (new Theme)->newFromBuilder($attributes)->definition();
    }

    /**
     * @return array{site: array<string, mixed>|null, theme: array<string, mixed>|null}
     */
    private function cached(): array
    {
        /** @var array{site: array<string, mixed>|null, theme: array<string, mixed>|null} */
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $site = Site::query()->orderBy('id')->first();
            $key = $site === null ? CreateSiteFromConfig::attributes()['theme_key'] : $site->theme_key;

            if ($this->themes->isBuiltIn($key)) {
                return ['site' => $site?->getAttributes(), 'theme' => null];
            }

            $theme = Theme::query()->where('key', $key)->first();

            if ($theme === null) {
                Log::warning('The site\'s theme does not exist; using the classic theme.', ['theme' => $key]);
            }

            return ['site' => $site?->getAttributes(), 'theme' => $theme?->getAttributes()];
        });
    }
}
