<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Grants a user edit rights over a term. term_id holds the term's master_id
 * so the grant survives new versions.
 *
 * @property int $id
 * @property int $term_id
 * @property int $user_id
 */
#[Table('terms_editors')]
#[Fillable(['term_id', 'user_id'])]
class TermEditor extends Model
{
    /**
     * Replace the editors of one term.
     *
     * @param  list<int>  $userIds
     */
    public static function assignUsers(int $masterId, array $userIds): void
    {
        DB::transaction(function () use ($masterId, $userIds): void {
            static::query()->where('term_id', $masterId)->delete();
            self::insertPairs(array_map(fn (int $userId): array => [$masterId, $userId], $userIds));
        });
    }

    /**
     * Replace the terms one user edits.
     *
     * @param  list<int>  $masterIds
     */
    public static function assignTerms(int $userId, array $masterIds): void
    {
        DB::transaction(function () use ($userId, $masterIds): void {
            static::query()->where('user_id', $userId)->delete();
            self::insertPairs(array_map(fn (int $masterId): array => [$masterId, $userId], $masterIds));
        });
    }

    /**
     * @param  list<array{int, int}>  $pairs  [master_id, user_id]
     */
    private static function insertPairs(array $pairs): void
    {
        $now = now();

        static::query()->insert(array_map(fn (array $pair): array => [
            'term_id' => $pair[0],
            'user_id' => $pair[1],
            'created_at' => $now,
            'updated_at' => $now,
        ], $pairs));
    }
}
