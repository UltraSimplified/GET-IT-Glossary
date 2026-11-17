<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Terms\AssignTermEditors;
use App\Actions\Terms\SaveTermVersion;
use App\Http\Requests\Admin\TermRequest;
use App\Models\Term;
use App\Models\TermEditor;
use App\Models\User;
use App\Support\TermPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Term management for administrators and the editors assigned to a term.
 * Comparing and rolling back versions lives in TermVersionController.
 */
class TermController extends TermAdminController
{
    /**
     * Administrators see every term; editors see the terms assigned to them.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $terms = Term::query()->current()
            ->when(! $user->is_admin, fn ($query) => $query->whereIn(
                'master_id',
                TermEditor::query()->where('user_id', $user->id)->select('term_id'),
            ))
            ->with(['editors' => fn ($query) => $query->select('users.id', 'users.first_name', 'users.last_name')])
            ->orderBy('term')
            ->get()
            ->map(fn (Term $term): array => [
                'id' => $term->id,
                'master_id' => $term->master_id,
                'term' => $term->term,
                'path' => $term->path(),
                'tags' => TermPresenter::tags($term->tags),
                'version' => $term->version,
                'status' => $term->term_status,
                'idgi' => $term->idgiPercent(),
                'editors' => $term->editors->map(fn (User $editor): string => $editor->name)->all(),
            ]);

        return Inertia::render('admin/terms/Index', [
            'terms' => $terms,
            'canCreate' => $user->is_admin,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/terms/Create', [
            ...$this->formOptions(),
            'editors' => $this->editorOptions(null),
        ]);
    }

    public function store(TermRequest $request, AssignTermEditors $assignEditors): RedirectResponse
    {
        $term = DB::transaction(function () use ($request): Term {
            $term = Term::query()->create([...$request->termAttributes(), 'version' => 1]);

            // The first version of a term is its own master.
            $term->forceFill(['master_id' => $term->id])->save();

            return $term;
        });

        $assignEditors($term, $request->validated('editors', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Term created.')]);

        return to_route('terms.index');
    }

    /**
     * The management screens link here; the term itself lives on the public site.
     */
    public function show(Term $term): RedirectResponse
    {
        return redirect($term->path());
    }

    public function edit(Request $request, Term $term): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->canEdit($term)) {
            return $this->notAnEditor();
        }

        // Old versions are history: send the editor to the version that is current.
        $current = SaveTermVersion::current($term);

        if ($current && $current->isNot($term)) {
            return to_route('terms.edit', $current);
        }

        $term->load('updater');

        return Inertia::render('admin/terms/Edit', [
            ...$this->formOptions(),
            'term' => [
                ...$term->only([
                    'id', 'master_id', 'term', 'term_ordered', 'term_status', 'do_not_use',
                    'see_also', 'synonyms', 'warning', 'definition', 'explanation',
                    'common_variants', 'pronunciation', 'video', 'test', 'cartoon', 'example', 'version',
                ]),
                'tags' => TermPresenter::tags($term->tags),
                'path' => $term->path(),
            ],
            'meta' => TermPresenter::versionMeta($term),
            'editors' => $user->is_admin ? $this->editorOptions($term) : null,
        ]);
    }

    public function update(
        TermRequest $request,
        Term $term,
        SaveTermVersion $versions,
        AssignTermEditors $assignEditors,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        if (! $user->canEdit($term)) {
            return $this->notAnEditor();
        }

        $asNewVersion = $request->validated('mode', 'update') === 'version';

        $saved = $asNewVersion
            ? $versions->saveAsNew($term, $request->termAttributes(), $user)
            : $versions->update($term, $request->termAttributes());

        // Only administrators decide who edits a term.
        if ($user->is_admin && $request->has('editors')) {
            $assignEditors($saved, $request->validated('editors', []));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $asNewVersion ? __('New version saved.') : __('Term updated.'),
        ]);

        // Drafts and archived terms have no public page to go back to.
        return $saved->enabled ? redirect($saved->path()) : to_route('terms.index');
    }

    /**
     * @return array{tagOptions: list<string>, statuses: list<string>}
     */
    private function formOptions(): array
    {
        return [
            'tagOptions' => config('glossary.tags'),
            'statuses' => Term::EDITABLE_STATUSES,
        ];
    }

    /**
     * The people who can be made editors of a term (administrators can
     * already edit everything) and who is assigned now.
     *
     * @return array{all: list<array{id: int, name: string}>, assigned: list<int>}
     */
    private function editorOptions(?Term $term): array
    {
        $people = User::query()->where('is_admin', false)->orderBy('last_name')->orderBy('first_name')->get();

        return [
            'all' => array_values($people->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])->all()),
            'assigned' => $term
                ? array_values(array_map(intval(...), TermEditor::query()->where('term_id', $term->master_id)->pluck('user_id')->all()))
                : [],
        ];
    }
}
