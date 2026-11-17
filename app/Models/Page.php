<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * An editable content page (About, API, Get in touch…).
 *
 * @property int $id
 * @property string $title
 * @property string $body
 * @property string $slug
 * @property int|null $menu
 * @property int|null $position
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'body', 'slug', 'menu', 'position', 'updated_by'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    public const int MENU_ABOUT = 1;

    public const int MENU_API = 2;

    public const int MENU_CONTACT = 3;

    /**
     * URL prefix for each footer column.
     *
     * @var array<int, string>
     */
    public const array MENU_PREFIXES = [
        self::MENU_ABOUT => 'about',
        self::MENU_API => 'the-api',
        self::MENU_CONTACT => 'get-in-touch',
    ];

    /**
     * Slugs of the pages that carry a contact form.
     *
     * @var list<string>
     */
    public const array FORM_SLUGS = ['feedback', 'suggest-term', 'suggest-translation'];

    protected static function booted(): void
    {
        static::creating(function (Page $page): void {
            if (blank($page->slug)) {
                $page->slug = static::uniqueSlug(strip_tags($page->title));
            }
        });
    }

    /**
     * Build a slug from a title that no other page is using.
     */
    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * The public URL path of this page.
     */
    public function path(): string
    {
        if ($this->slug === 'home') {
            return '/';
        }

        $prefix = in_array($this->slug, self::FORM_SLUGS, true)
            ? self::MENU_PREFIXES[self::MENU_CONTACT]
            : (self::MENU_PREFIXES[$this->menu] ?? self::MENU_PREFIXES[self::MENU_ABOUT]);

        return "/{$prefix}/{$this->slug}";
    }
}
