<?php

namespace App\Actions\Terms;

use App\Mail\TermChanged;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Saves a term in one of the two ways the edit screen offers, or brings an
 * earlier version back.
 *
 * Keeping history means demoting the current row to a REVISION and
 * inserting the new wording as the next version; administrators are
 * emailed whenever that happens.
 */
final class SaveTermVersion
{
    /**
     * Overwrite this version in place (for corrections).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Term $term, array $attributes): Term
    {
        $term->update($attributes);

        return $term;
    }

    /**
     * Keep this version as history and save the attributes as the next one.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveAsNew(Term $term, array $attributes, User $by): Term
    {
        $saved = DB::transaction(function () use ($term, $attributes): Term {
            $this->retire($term);

            return Term::query()->create([
                ...$attributes,
                'master_id' => $term->master_id,
                'version' => $term->version + 1,
                'hits' => 0,
                'IDGI' => 0,
            ]);
        });

        $this->notifyAdmins(new TermChanged($saved, $by));

        return $saved;
    }

    /**
     * Copy an earlier version to become the newest one, keeping the
     * current version's status.
     */
    public function rollBack(Term $earlier, Term $current, User $by): Term
    {
        $restored = DB::transaction(function () use ($earlier, $current, $by): Term {
            $restored = $earlier->replicate();
            $restored->version = $current->version + 1;
            $restored->updated_by = $by->id;
            $restored->enabled = $current->enabled;
            $restored->term_status = $current->term_status;

            $this->retire($current);
            $restored->save();

            return $restored;
        });

        $this->notifyAdmins(new TermChanged($restored, $by, rolledBack: true));

        return $restored;
    }

    /**
     * The newest version of the term this row belongs to.
     */
    public static function current(Term $term): ?Term
    {
        return Term::query()->where('master_id', $term->master_id)
            ->orderByDesc('version')->orderByDesc('id')
            ->first();
    }

    private function retire(Term $term): void
    {
        $term->update(['enabled' => false, 'term_status' => Term::STATUS_REVISION]);
    }

    private function notifyAdmins(TermChanged $message): void
    {
        $admins = User::query()->where('is_admin', true)->where('enabled', true)->get();

        if ($admins->isNotEmpty()) {
            Mail::to($admins)->send($message);
        }
    }
}
