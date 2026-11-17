<?php

namespace App\Services\LegacyImport;

/**
 * One INSERT statement from a dump, collected line by line until it ends.
 */
final class InsertStatement
{
    private string $values = '';

    private bool $inString = false;

    /**
     * @param  list<string>  $columns
     * @param  bool  $wanted  Whether the import uses this table (others are only skipped past)
     */
    public function __construct(
        public readonly string $table,
        public readonly array $columns,
        public readonly bool $wanted,
    ) {}

    /**
     * Add the next line of the statement. Returns true once the statement
     * is complete.
     */
    public function append(string $line): bool
    {
        // Track whether the line ends inside a quoted string, so a ";" inside
        // a definition is not mistaken for the end of the statement.
        $unescaped = preg_replace('/\\\\./s', '', $line) ?? $line;

        if (substr_count($unescaped, "'") % 2 === 1) {
            $this->inString = ! $this->inString;
        }

        if ($this->wanted) {
            $this->values .= $line;
        }

        return ! $this->inString && str_ends_with(rtrim($line), ';');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return SqlValueParser::rows($this->columns, $this->values);
    }
}
