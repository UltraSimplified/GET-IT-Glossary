<?php

namespace App\Services\LegacyImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Writes legacy rows into the current database, keeping their ids so term
 * versions, comments, editor assignments and "updated by" references stay
 * connected.
 */
final class LegacyGlossaryWriter
{
    /**
     * Tables read from the dump, in the order they are written.
     *
     * @var list<string>
     */
    public const array TABLES = ['users', 'terms', 'terms_editors', 'pages', 'comments', 'searches'];

    /**
     * Everything the import replaces, children before the rows they point at.
     *
     * @var list<string>
     */
    private const array CLEAR_ORDER = [
        'comments', 'terms_editors', 'searches', 'terms', 'pages',
        'passkeys', 'sessions', 'password_reset_tokens', 'users',
    ];

    /** Keeps each statement well under SQLite's bound-parameter limit. */
    private const int PARAMETERS_PER_STATEMENT = 5000;

    public function __construct(private readonly LegacyRowMapper $mapper = new LegacyRowMapper) {}

    public function clear(): void
    {
        foreach (array_filter(self::CLEAR_ORDER, Schema::hasTable(...)) as $table) {
            DB::table($table)->delete();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows  Rows as they appear in the dump
     * @return int How many rows were written
     */
    public function write(string $table, array $rows): int
    {
        $rows = $this->mapper->map($table, $rows);
        $perChunk = max(1, intdiv(self::PARAMETERS_PER_STATEMENT, max(1, count($rows[0] ?? []))));

        foreach (array_chunk($rows, $perChunk) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        return count($rows);
    }

    /**
     * Terms added through the old "create term" form were saved with
     * master_id 0, which would make them all look like versions of one
     * another. Give each its own group: rows with the same name share the
     * earliest row's id, everything else points at itself.
     *
     * @return int How many terms were repaired
     */
    public function repairMasterIds(): int
    {
        $orphans = DB::table('terms')->where('master_id', 0)->orderBy('id')->get(['id', 'term']);

        foreach ($orphans->groupBy(fn (object $row): string => mb_strtolower(trim($row->term))) as $group) {
            DB::table('terms')->whereIn('id', $group->pluck('id'))->update(['master_id' => $group->first()->id]);
        }

        return $orphans->count();
    }

    /**
     * Carry on numbering where the old database left off, so a new record
     * never reuses the id of one deleted there (old versions still name
     * deleted users in "updated by").
     *
     * @param  array<string, int>  $autoIncrement  Next id per table in the old database
     */
    public function continueNumbering(array $autoIncrement): void
    {
        foreach (self::TABLES as $table) {
            $next = max((int) DB::table($table)->max('id') + 1, $autoIncrement[$table] ?? 0);

            if ($next > 1) {
                $this->setNextId($table, $next);
            }
        }
    }

    private function setNextId(string $table, int $next): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite' => DB::table('sqlite_sequence')->updateOrInsert(['name' => $table], ['seq' => $next - 1]),
            'mysql', 'mariadb' => DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$next}"),
            'pgsql' => DB::select("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), {$next} - 1)"),
            default => null,
        };
    }
}
