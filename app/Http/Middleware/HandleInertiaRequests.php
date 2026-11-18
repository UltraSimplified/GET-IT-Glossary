<?php

namespace App\Http\Middleware;

use App\Support\CurrentSite;
use App\Support\Glossary;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Fortify\Features;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        // The page head carries the site's name, theme styles and fonts, which
        // Inertia visits never reload. Changing the site or its theme changes
        // the version, so the next visit loads the whole page afresh.
        $current = app(CurrentSite::class);
        $site = $current->site()->only(['name', 'locale', 'font_stylesheet_url', 'theme_key', 'updated_at']);
        $theme = $current->theme();

        return parent::version($request).'.'.substr(md5((string) json_encode([$site, $theme->key, $theme->settings])), 0, 12);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $current = app(CurrentSite::class);

        return [
            ...parent::share($request),
            'name' => fn (): string => $current->site()->name,
            'auth' => [
                'user' => $request->user(),
            ],
            // Everything the site chrome needs: branding, the A–Z index and the footer.
            'glossary' => fn (): array => [
                'title' => $current->site()->name,
                'tagline' => $current->site()->tagline,
                'logo' => $current->site()->logo ?? $current->theme()->logo() ?? '',
                'idgi' => $current->site()->idgiPictures(),
                'locale' => app()->getLocale(),
                'letters' => Glossary::letters(),
                'footer' => Glossary::footer(),
                'canRegister' => Features::enabled(Features::registration()),
            ],
            // The active theme's switches that change markup, and its accents.
            'theme' => fn (): array => $current->theme()->shared(),
        ];
    }

    /**
     * Props the browser keeps after the first visit instead of receiving
     * them again with every page.
     *
     * @return array<string, mixed>
     */
    public function shareOnce(Request $request): array
    {
        return [
            ...parent::shareOnce($request),
            'translations' => fn (): array => Glossary::translations(),
        ];
    }
}
