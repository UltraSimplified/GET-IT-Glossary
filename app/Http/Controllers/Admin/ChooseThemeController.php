<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Sets the theme the whole site is drawn with. Visitors see it on the next
 * page they open.
 */
class ChooseThemeController extends Controller
{
    public function __invoke(Request $request, ThemeRegistry $themes): RedirectResponse
    {
        $themesByKey = [];

        foreach ($themes->all() as $theme) {
            $themesByKey[$theme->key] = $theme;
        }

        $validated = $request->validate([
            'theme' => ['required', 'string', Rule::in(array_keys($themesByKey))],
        ]);

        $chosen = $themesByKey[(string) $validated['theme']];
        Site::query()->orderBy('id')->firstOrFail()->update(['theme_key' => $chosen->key]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('The site now uses the :theme theme.', ['theme' => $chosen->name])]);

        return back();
    }
}
