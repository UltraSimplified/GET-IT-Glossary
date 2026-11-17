<?php

namespace App\Services\LegacyImport;

use Generator;
use RuntimeException;

/**
 * Streams a mysqldump / phpMyAdmin export (plain or gzipped) and hands
 * back the INSERT statements for the tables asked for, without ever
 * holding the whole file in memory.
 */
final class SqlDumpReader
{
    private const string INSERT = '/^INSERT INTO `(\w+)` \(([^)]*)\) VALUES\s*/';

    private const string INSERT_WITHOUT_COLUMNS = '/^INSERT INTO `(\w+)` VALUES/';

    /** @var array<string, int> Next id each table used, from the ALTER TABLE statements. */
    private array $autoIncrement = [];

    private ?string $alterTable = null;

    /**
     * @param  list<string>  $tables
     */
    public function __construct(
        private readonly string $path,
        private readonly array $tables,
    ) {}

    /**
     * @return Generator<int, InsertStatement>
     */
    public function inserts(): Generator
    {
        $handle = gzopen($this->path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Cannot open [{$this->path}].");
        }

        try {
            yield from $this->statementsFrom($handle);
        } finally {
            gzclose($handle);
        }
    }

    /**
     * The AUTO_INCREMENT values seen so far; complete once inserts() has
     * been read to the end.
     *
     * @return array<string, int>
     */
    public function autoIncrement(): array
    {
        return $this->autoIncrement;
    }

    /**
     * @param  resource  $handle
     * @return Generator<int, InsertStatement>
     */
    private function statementsFrom($handle): Generator
    {
        $statement = null;

        while (($line = gzgets($handle)) !== false) {
            if ($statement === null) {
                [$statement, $line] = $this->begin($line);
            }

            if ($statement === null || ! $statement->append($line)) {
                continue;
            }

            if ($statement->wanted) {
                yield $statement;
            }

            $statement = null;
        }
    }

    /**
     * Start an INSERT statement if this line opens one. Lines outside
     * statements are only checked for AUTO_INCREMENT values.
     *
     * @return array{InsertStatement|null, string} The statement and what is left of the line
     */
    private function begin(string $line): array
    {
        if (preg_match(self::INSERT, $line, $match)) {
            $columns = array_map(fn (string $column): string => trim($column, " `\t"), explode(',', $match[2]));
            $statement = new InsertStatement($match[1], $columns, in_array($match[1], $this->tables, true));

            return [$statement, substr($line, strlen($match[0]))];
        }

        if (preg_match(self::INSERT_WITHOUT_COLUMNS, $line, $match) && in_array($match[1], $this->tables, true)) {
            throw new RuntimeException('The dump\'s INSERT statements have no column names. Export it again with complete inserts (phpMyAdmin: "Include column names in every INSERT statement"; mysqldump: --complete-insert).');
        }

        $this->noteAutoIncrement($line);

        return [null, $line];
    }

    /**
     * phpMyAdmin ends a dump with "ALTER TABLE `x`" followed by a line
     * holding "AUTO_INCREMENT=n".
     */
    private function noteAutoIncrement(string $line): void
    {
        if (preg_match('/^ALTER TABLE `(\w+)`/', $line, $match)) {
            $this->alterTable = $match[1];
        } elseif ($this->alterTable !== null && preg_match('/AUTO_INCREMENT=(\d+)/', $line, $match)) {
            $this->autoIncrement[$this->alterTable] = (int) $match[1];
        }
    }
}
