<?php

namespace App\Models;

use App\Support\CurrentSite;
use Carbon\CarbonImmutable;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * What makes an install itself: its name, tagline, logo, interface
 * language, optional font stylesheet and chosen theme. An install has
 * exactly one site.
 *
 * @property int $id
 * @property string $name
 * @property string $tagline
 * @property string $locale
 * @property string|null $logo Public URL path of the site's own logo.
 * @property string|null $font_stylesheet_url
 * @property list<string>|null $font_families Families the font stylesheet declares.
 * @property string $theme_key
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'tagline', 'locale', 'logo', 'font_stylesheet_url', 'font_families', 'theme_key'])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'font_families' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(CurrentSite::class)->forget());
        static::deleted(fn () => app(CurrentSite::class)->forget());
    }

    /**
     * Where the font stylesheet is served from, for a preconnect hint.
     */
    public function fontStylesheetOrigin(): ?string
    {
        $url = $this->font_stylesheet_url;
        $scheme = is_string($url) ? parse_url($url, PHP_URL_SCHEME) : null;
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;

        return is_string($scheme) && is_string($host) ? $scheme.'://'.$host : null;
    }

    /**
     * @return list<string>
     */
    public function fontFamilies(): array
    {
        return array_values(array_filter($this->font_families ?? [], is_string(...)));
    }
}
