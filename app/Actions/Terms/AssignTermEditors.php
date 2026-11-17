<?php

namespace App\Actions\Terms;

use App\Mail\NewTermEditor;
use App\Models\Term;
use App\Models\TermEditor;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Sets who may edit a term and emails the people who are new to it.
 */
final class AssignTermEditors
{
    /**
     * @param  array<int, int|string>  $userIds
     */
    public function __invoke(Term $term, array $userIds): void
    {
        $userIds = array_values(array_unique(array_map(intval(...), $userIds)));
        $existing = array_map(intval(...), TermEditor::query()->where('term_id', $term->master_id)->pluck('user_id')->all());

        TermEditor::assignUsers($term->master_id, $userIds);

        foreach (User::query()->whereIn('id', array_diff($userIds, $existing))->get() as $editor) {
            Mail::to($editor->email, $editor->name)->send(new NewTermEditor($editor, $term));
        }
    }
}
