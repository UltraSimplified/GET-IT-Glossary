<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Term;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [[
            'loc' => url('/'),
            'lastmod' => null,
            'priority' => '1.0',
            'changefreq' => 'daily',
        ]];

        Page::query()->whereNotIn('slug', ['home', ''])->latest('updated_at')->get()
            ->each(function (Page $page) use (&$urls): void {
                $urls[] = [
                    'loc' => url($page->path()),
                    'lastmod' => $page->updated_at?->toAtomString(),
                    'priority' => '0.6',
                    'changefreq' => 'monthly',
                ];
            });

        Term::query()->live()->orderBy('term')->get(['id', 'term', 'updated_at'])
            ->each(function (Term $term) use (&$urls): void {
                $urls[] = [
                    'loc' => url(mb_strtolower($term->path())),
                    'lastmod' => $term->updated_at?->toAtomString(),
                    'priority' => '1.0',
                    'changefreq' => 'daily',
                ];
            });

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
