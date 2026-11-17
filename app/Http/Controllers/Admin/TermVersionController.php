<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Terms\SaveTermVersion;
use App\Models\Term;
use App\Models\User;
use App\Support\TermPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Comparing a term's earlier versions with the current one, and bringing
 * an earlier version back.
 */
class TermVersionController extends TermAdminController
{
    /**
     * Compare an earlier version with the current one, side by side.
     */
    public function show(Request $request, int $master, int $version): Response|RedirectResponse
    {
        $versions = Term::query()->where('master_id', $master)
            ->orderBy('version')->orderBy('id')
            ->with('updater')
            ->get();

        $current = $versions->last();
        $selected = $versions->firstWhere('version', $version) ?? $versions->get($version - 1);

        abort_unless($current instanceof Term && $selected instanceof Term, 404);

        /** @var User $user */
        $user = $request->user();

        if (! $user->canEdit($current)) {
            return $this->notAnEditor();
        }

        return Inertia::render('admin/terms/Compare', [
            'masterId' => $master,
            'selected' => TermPresenter::comparable($selected),
            'current' => TermPresenter::comparable($current),
            'versions' => $versions->reject(fn (Term $term): bool => $term->is($current))
                ->pluck('version')->unique()->values(),
        ]);
    }

    /**
     * Bring an earlier version back by saving a copy of it as the newest version.
     */
    public function rollback(Request $request, Term $term, SaveTermVersion $versions): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $current = SaveTermVersion::current($term);

        if (! $current || ! $user->canEdit($current)) {
            return $this->notAnEditor();
        }

        abort_if($current->is($term), 422, 'This is already the current version.');

        $restored = $versions->rollBack($term, $current, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Version :old is back, saved as version :version.', ['old' => $term->version, 'version' => $restored->version]),
        ]);

        return to_route('terms.index');
    }
}
