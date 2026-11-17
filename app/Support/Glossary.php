<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Term;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Site-wide glossary data shared with every page: which letters have
 * terms, the footer menu and the interface strings.
 */
class Glossary
{
    private const string LETTERS_CACHE_KEY = 'glossary.letters';

    /**
     * Upper-case initials that have at least one published term, so the
     * A–Z index can mute the letters that lead nowhere.
     *
     * @return list<string>
     */
    public static function letters(): array
    {
        return Cache::remember(self::LETTERS_CACHE_KEY, now()->addMinutes(10), function (): array {
            $letters = [];

            Term::query()->live()->toBase()->select(['term', 'term_ordered'])->orderBy('id')
                ->each(function (object $row) use (&$letters): void {
                    foreach ([$row->term, $row->term_ordered] as $name) {
                        $initial = mb_strtoupper(mb_substr(trim((string) $name), 0, 1));

                        if ($initial !== '') {
                            $letters[$initial] = true;
                        }
                    }
                });

            $letters = array_keys($letters);
            sort($letters);

            return $letters;
        });
    }

    public static function forgetLetters(): void
    {
        Cache::forget(self::LETTERS_CACHE_KEY);
    }

    /**
     * The three footer columns. Pages choose their column and order in the
     * page editor ("Footer column"); until any page has done so the
     * original fixed set of links is used.
     *
     * @return list<array{title: string, links: list<array{label: string, href: string}>}>
     */
    public static function footer(): array
    {
        $pages = Page::query()
            ->whereIn('menu', array_keys(Page::MENU_PREFIXES))
            ->where('slug', '!=', 'home')
            ->orderBy('menu')->orderBy('position')->orderBy('id')
            ->get(['id', 'title', 'slug', 'menu']);

        $links = $pages->isEmpty() ? self::defaultLinks() : self::pageLinks($pages);

        return [
            ['title' => self::text('interface.about'), 'links' => [
                ['label' => self::text('interface.top-ten'), 'href' => '/about/top-ten'],
                ...$links[Page::MENU_ABOUT],
            ]],
            ['title' => self::text('interface.api'), 'links' => $links[Page::MENU_API]],
            ['title' => self::text('interface.contact'), 'links' => $links[Page::MENU_CONTACT]],
        ];
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return array{1: list<array{label: string, href: string}>, 2: list<array{label: string, href: string}>, 3: list<array{label: string, href: string}>}
     */
    private static function pageLinks(Collection $pages): array
    {
        $column = fn (int $menu): array => array_values($pages
            ->where('menu', $menu)
            ->map(fn (Page $page): array => [
                'label' => html_entity_decode(strip_tags($page->title), ENT_QUOTES | ENT_HTML5),
                'href' => $page->path(),
            ])
            ->all());

        return [
            Page::MENU_ABOUT => $column(Page::MENU_ABOUT),
            Page::MENU_API => $column(Page::MENU_API),
            Page::MENU_CONTACT => $column(Page::MENU_CONTACT),
        ];
    }

    /**
     * The links the original site hard-coded, used until pages are placed.
     *
     * @return array{1: list<array{label: string, href: string}>, 2: list<array{label: string, href: string}>, 3: list<array{label: string, href: string}>}
     */
    private static function defaultLinks(): array
    {
        return [
            Page::MENU_ABOUT => [
                ['label' => 'Who we are', 'href' => '/about/who-we-are'],
                ['label' => 'About GET-IT', 'href' => '/about/purpose-of-get-it'],
                ['label' => 'Development', 'href' => '/about/development'],
            ],
            Page::MENU_API => [
                ['label' => 'What is the API?', 'href' => '/the-api/what-is-the-api'],
                ['label' => 'Embed GET-IT on your own web site', 'href' => '/the-api/embed-get-it'],
                ['label' => 'Download the WordPress plugin', 'href' => '/the-api/download-wordpress-plugin'],
            ],
            Page::MENU_CONTACT => [
                ['label' => 'Send us your feedback', 'href' => '/get-in-touch/feedback'],
                ['label' => 'Suggest a term', 'href' => '/get-in-touch/suggest-term'],
                ['label' => 'Suggest a translation', 'href' => '/get-in-touch/suggest-translation'],
            ],
        ];
    }

    /**
     * One interface string. A missing key shows as itself rather than blank.
     */
    private static function text(string $key): string
    {
        $line = __($key);

        return is_string($line) ? $line : $key;
    }

    /**
     * Interface strings for the current locale, with the fallback locale
     * filling any keys a translation has not caught up on yet.
     *
     * @return array<string, string>
     */
    public static function translations(): array
    {
        $fallback = trans('interface', [], config('app.fallback_locale'));
        $current = trans('interface');

        return array_replace(
            is_array($fallback) ? $fallback : [],
            is_array($current) ? $current : [],
        );
    }
}
