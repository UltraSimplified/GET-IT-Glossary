<?php

namespace App\Console\Commands;

use App\Services\LegacyImport\LegacyGlossaryWriter;
use App\Services\LegacyImport\SqlDumpReader;
use App\Support\Glossary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Loads a mysqldump / phpMyAdmin export of the legacy Laravel 5 glossary
 * database into the current database, whatever driver it uses (SQLite
 * locally, MySQL in production).
 */
class ImportLegacyGlossary extends Command
{
    protected $signature = 'glossary:import
        {file : Path to the .sql or .sql.gz dump}
        {--searches : Also import the search log (can be millions of rows)}
        {--force : Replace the current data without asking}';

    protected $description = 'Replace users, terms, versions, pages and comments with those from a legacy glossary database dump';

    /**
     * What each table's import step is called on screen.
     *
     * @var array<string, string>
     */
    private const array STEPS = [
        'users' => 'Importing users',
        'terms' => 'Importing terms and their versions',
        'terms_editors' => 'Importing term editors',
        'pages' => 'Importing pages',
        'comments' => 'Importing comments',
    ];

    private int $searchesImported = 0;

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file) || ! is_readable($file)) {
            $this->components->error("Cannot read [{$file}].");

            return self::FAILURE;
        }

        if (! $this->confirmed()) {
            return self::FAILURE;
        }

        $started = microtime(true);
        $reader = new SqlDumpReader($file, LegacyGlossaryWriter::TABLES);
        $writer = new LegacyGlossaryWriter;

        DB::transaction(fn () => $this->import($reader, $writer));

        Glossary::forgetLetters();

        $this->summary();
        $this->components->info(sprintf('Done in %.1f seconds.', microtime(true) - $started));

        return self::SUCCESS;
    }

    private function confirmed(): bool
    {
        $connection = DB::connection();

        return $this->option('force') || $this->confirm(
            "This replaces every user, term, page and comment in the [{$connection->getName()}] database ({$connection->getDatabaseName()}). Continue?",
        );
    }

    private function import(SqlDumpReader $reader, LegacyGlossaryWriter $writer): void
    {
        $writer->clear();

        $rows = [];
        $this->components->task('Reading the dump', function () use ($reader, $writer, &$rows): void {
            $rows = $this->read($reader, $writer);
        });

        foreach (self::STEPS as $table => $label) {
            $this->components->task($label, fn () => $writer->write($table, $rows[$table] ?? []));
        }

        $repaired = $writer->repairMasterIds();

        if ($repaired > 0) {
            $this->components->info("{$repaired} term(s) had no version group (master_id 0) and were given their own.");
        }

        $writer->continueNumbering($reader->autoIncrement());
    }

    /**
     * Collect the rows of every table, except the search log: that can be
     * millions of rows, so it is written statement by statement as it
     * streams past.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function read(SqlDumpReader $reader, LegacyGlossaryWriter $writer): array
    {
        $rows = [];

        foreach ($reader->inserts() as $insert) {
            if ($insert->table !== 'searches') {
                $rows[$insert->table] = [...($rows[$insert->table] ?? []), ...$insert->rows()];
            } elseif ($this->option('searches')) {
                $this->searchesImported += $writer->write('searches', $insert->rows());
            }
        }

        return $rows;
    }

    private function summary(): void
    {
        $terms = DB::table('terms');

        $this->newLine();
        $this->table(['Imported', 'Count'], [
            ['Users', DB::table('users')->count()],
            ['  of whom administrators', DB::table('users')->where('is_admin', true)->count()],
            ['Terms (current versions)', (clone $terms)->where('term_status', '!=', 'REVISION')->count()],
            ['  published', (clone $terms)->where('enabled', true)->count()],
            ['  terms with earlier versions', DB::query()->fromSub(
                (clone $terms)->select('master_id')->groupBy('master_id')->havingRaw('COUNT(*) > 1'),
                'versioned',
            )->count()],
            ['Earlier versions (revisions)', (clone $terms)->where('term_status', 'REVISION')->count()],
            ['Editor assignments', DB::table('terms_editors')->count()],
            ['Pages', DB::table('pages')->count()],
            ['Comments', DB::table('comments')->count()],
            ['Search log entries', $this->option('searches') ? $this->searchesImported : 'skipped (use --searches)'],
        ]);
    }
}
