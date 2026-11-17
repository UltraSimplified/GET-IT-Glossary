<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Themes\CopyTheme;
use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\Theme;
use App\Support\CurrentSite;
use App\Support\TermPresenter;
use App\Support\UploadedLogos;
use App\Themes\ThemeDefinition;
use App\Themes\ThemeRegistry;
use App\Themes\ThemeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Listing, copying, editing and deleting themes. Built-in themes can be
 * copied but not changed; the theme the site uses cannot be deleted.
 */
class ThemeController extends Controller
{
    private const string LOGO_FOLDER = 'themes';

    public function __construct(
        private readonly ThemeRegistry $themes,
        private readonly CurrentSite $current,
    ) {}

    public function index(): Response
    {
        $active = $this->current->theme()->key;

        return Inertia::render('admin/themes/Index', [
            'themes' => array_map(fn (ThemeDefinition $theme): array => [
                'key' => $theme->key,
                'name' => $theme->name,
                'description' => $theme->description,
                'built_in' => $theme->builtIn,
                'based_on' => $theme->basedOn === null ? null : $this->themes->find($theme->basedOn)?->name,
                'active' => $theme->key === $active,
                'colours' => $theme->settings['colours']['light'],
                'accents' => $theme->settings['accents'],
                'has_dark' => $theme->hasDark(),
                'letter_shape' => $theme->settings['letter_shape'],
                'font' => $theme->settings['fonts']['body']['family'],
            ], $this->themes->all()),
        ]);
    }

    /**
     * Copy a theme, then open the copy in the editor.
     */
    public function store(Request $request, CopyTheme $copy): SymfonyResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'string', Rule::in(array_map(fn (ThemeDefinition $theme): string => $theme->key, $this->themes->all()))],
            'name' => ['required', 'string', 'max:100'],
        ], ['name.required' => 'Please give the new theme a name.']);

        $source = $this->themes->find((string) $validated['from']);
        abort_if($source === null, 404);

        $theme = $copy->handle($source, trim((string) $validated['name']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme copied.')]);

        // A full page load, so the editor can load every bundled font for its preview.
        return Inertia::location(route('themes.edit', $theme->key));
    }

    public function edit(string $theme): Response
    {
        $custom = $this->custom($theme);

        // Not `theme`: that is the shared prop the site layout draws with.
        return Inertia::render('admin/themes/Edit', [
            'editing' => [
                'key' => $custom->key,
                'name' => $custom->name,
                'based_on' => $custom->based_on === null ? null : $this->themes->find($custom->based_on)?->name,
                'active' => $custom->key === $this->current->theme()->key,
                'settings' => $custom->definition()->settings,
            ],
            'fonts' => [
                'bundled' => ThemeSettings::bundledFamilies(),
                'site' => $this->siteFamilies($custom),
            ],
            'sample' => $this->sampleTerms(),
        ]);
    }

    public function update(Request $request, string $theme): RedirectResponse
    {
        $custom = $this->custom($theme);

        $request->validate(['name' => ['required', 'string', 'max:100'], 'logo' => UploadedLogos::RULES], [
            'name.required' => 'Please give the theme a name.',
            ...UploadedLogos::MESSAGES,
        ]);

        $validator = ThemeSettings::validator($request->all(), $this->siteFamilies($custom), 'settings.');
        $validator->validate();

        $settings = $request->input('settings');
        $settings = ThemeSettings::normalise(is_array($settings) ? $settings : []);
        $settings['logo'] = $this->logo($custom, $request->file('logo'), $request->boolean('remove_logo'));

        $custom->update(['name' => $request->string('name')->trim()->value(), 'settings' => $settings]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme saved.')]);

        return to_route('themes.edit', $custom->key);
    }

    public function destroy(string $theme): RedirectResponse
    {
        $custom = $this->custom($theme);

        if ($custom->key === $this->current->theme()->key) {
            throw ValidationException::withMessages([
                'theme' => __('This is the site’s theme. Choose another theme for the site before deleting it.'),
            ]);
        }

        $logo = $custom->definition()->logo();
        $custom->delete();
        $this->deleteLogoIfUnused($logo);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme deleted.')]);

        return to_route('themes.index');
    }

    /**
     * The custom theme with this key. Built-in themes are refused.
     */
    private function custom(string $key): Theme
    {
        abort_if($this->themes->isBuiltIn($key), 403, __('Built-in themes can’t be changed. Copy one to make your own.'));

        return Theme::query()->where('key', $key)->firstOrFail();
    }

    /**
     * The families the site's font stylesheet provides, plus any site font
     * the theme already names (so a copy of classic keeps its Sirba).
     *
     * @return list<string>
     */
    private function siteFamilies(Theme $theme): array
    {
        $families = $this->current->site()->fontFamilies();

        foreach ($theme->definition()->settings['fonts'] as $font) {
            if ($font['source'] === 'site') {
                $families[] = $font['family'];
            }
        }

        return array_values(array_unique($families));
    }

    /**
     * The theme's default logo after saving: a new upload, none, or as it was.
     */
    private function logo(Theme $theme, mixed $upload, bool $remove): ?string
    {
        $current = $theme->definition()->logo();

        if (! $upload instanceof UploadedFile && ! $remove) {
            return $current;
        }

        $this->deleteLogoIfUnused($current, $theme->id);

        return $upload instanceof UploadedFile ? UploadedLogos::store($upload, self::LOGO_FOLDER) : null;
    }

    /**
     * Copies of a theme share its logo file, so the file goes only when no
     * other theme uses it.
     */
    private function deleteLogoIfUnused(?string $logo, ?int $except = null): void
    {
        if ($logo === null) {
            return;
        }

        $used = Theme::query()->where('settings->logo', $logo)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except))
            ->exists();

        if (! $used) {
            UploadedLogos::delete($logo, self::LOGO_FOLDER);
        }
    }

    /**
     * A few well-read terms to show in the editor's preview.
     *
     * @return list<array<string, mixed>>
     */
    private function sampleTerms(): array
    {
        $terms = Term::query()->live()
            ->whereNotNull('definition')->where('definition', '!=', '')
            ->orderByDesc('hits')->orderBy('id')->limit(3)->get();

        return array_values($terms->map(fn (Term $term): array => TermPresenter::summary($term))->all());
    }
}
