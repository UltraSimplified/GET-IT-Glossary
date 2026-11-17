<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The light, dark or system choice. Only offered while the site's theme
 * has dark colours; otherwise pages always show in light.
 */
class AppearanceController extends Controller
{
    public function __invoke(CurrentSite $current): Response|RedirectResponse
    {
        if (! $current->theme()->hasDark()) {
            return to_route('profile.edit');
        }

        return Inertia::render('settings/Appearance');
    }
}
