<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Shared by the screens that change terms.
 */
abstract class TermAdminController extends Controller
{
    /**
     * Send someone who may not edit a term back to their list of terms.
     */
    protected function notAnEditor(): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => __('You are not an editor of that term.'),
        ]);

        return to_route('terms.index');
    }
}
