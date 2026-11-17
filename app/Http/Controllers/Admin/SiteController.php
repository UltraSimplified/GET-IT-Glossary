<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Site\CreateSiteFromConfig;
use App\Actions\Site\ReadFontFamilies;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteRequest;
use App\Models\Site;
use App\Support\CurrentSite;
use App\Support\SiteLocales;
use App\Support\UploadedLogos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The site's details: name, tagline, language, logo and font stylesheet.
 */
class SiteController extends Controller
{
    private const string LOGO_FOLDER = 'site';

    public function edit(CurrentSite $current): Response
    {
        $site = $current->site();

        return Inertia::render('admin/site/Edit', [
            'site' => [
                'name' => $site->name,
                'tagline' => $site->tagline,
                'locale' => $site->locale,
                'logo' => $site->logo,
                'font_stylesheet_url' => $site->font_stylesheet_url,
                'font_families' => $site->fontFamilies(),
            ],
            'themeLogo' => $current->theme()->logo(),
            'themeName' => $current->theme()->name,
            'locales' => SiteLocales::options(),
        ]);
    }

    public function update(SiteRequest $request, ReadFontFamilies $readFonts): RedirectResponse
    {
        $site = Site::query()->orderBy('id')->first() ?? new Site(CreateSiteFromConfig::attributes());
        $previousUrl = $site->font_stylesheet_url;

        $site->fill([
            'name' => $request->string('name')->trim()->value(),
            'tagline' => $request->string('tagline')->trim()->value(),
            'locale' => $request->string('locale')->value(),
            'font_stylesheet_url' => $request->filled('font_stylesheet_url') ? $request->string('font_stylesheet_url')->trim()->value() : null,
        ]);

        $this->updateLogo($site, $request->file('logo'), $request->boolean('remove_logo'));

        $fontsRead = $this->updateFontFamilies($site, $readFonts, $previousUrl);

        $site->save();

        Inertia::flash('toast', $fontsRead
            ? ['type' => 'success', 'message' => __('Site details saved.')]
            : ['type' => 'warning', 'message' => __('Site details saved, but the font stylesheet could not be read, so its fonts can’t be offered to themes yet. Check the address, or try saving again later.')]);

        return to_route('site.edit');
    }

    /**
     * A new upload replaces the logo; removing it lets the theme's logo show.
     */
    private function updateLogo(Site $site, mixed $upload, bool $remove): void
    {
        if (! $remove && ! $upload instanceof UploadedFile) {
            return;
        }

        // Only logos uploaded here are deleted; a path from GLOSSARY_LOGO is left alone.
        UploadedLogos::delete($site->logo, self::LOGO_FOLDER);
        $site->logo = $upload instanceof UploadedFile ? UploadedLogos::store($upload, self::LOGO_FOLDER) : null;
    }

    /**
     * Read the families the font stylesheet declares. Returns false when it
     * could not be fetched; the address is still saved.
     */
    private function updateFontFamilies(Site $site, ReadFontFamilies $readFonts, ?string $previousUrl): bool
    {
        $url = $site->font_stylesheet_url;

        if ($url === null) {
            $site->font_families = null;

            return true;
        }

        $families = $readFonts->handle($url);

        if ($families === null) {
            // Keep what was read before, unless the address itself changed.
            if ($url !== $previousUrl) {
                $site->font_families = null;
            }

            return false;
        }

        $site->font_families = $families;

        return true;
    }
}
