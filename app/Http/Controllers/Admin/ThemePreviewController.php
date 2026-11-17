<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Support\CurrentSite;
use App\Themes\ThemeSettings;
use App\Themes\ThemeStyles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The CSS variables for a theme that has not been saved yet, for the
 * editor's live preview. The same ThemeStyles that draws the site makes
 * them, so the preview cannot drift from the real thing.
 */
class ThemePreviewController extends Controller
{
    public function __invoke(Request $request, ThemeStyles $styles, CurrentSite $current): JsonResponse
    {
        $families = $current->site()->fontFamilies();
        $theme = Theme::query()->where('key', $request->string('theme')->value())->first();

        foreach ($theme?->definition()->settings['fonts'] ?? [] as $font) {
            $families[] = $font['family'];
        }

        ThemeSettings::validator($request->all(), $families, 'settings.')->validate();

        $settings = $request->input('settings');

        return response()->json($styles->variables(ThemeSettings::normalise(is_array($settings) ? $settings : [])));
    }
}
