<?php

namespace App\Services\LegacyImport;

use RuntimeException;

/**
 * Reads the "(1,'a'),(2,'b');" part of a MySQL INSERT statement.
 */
final class SqlValueParser
{
    private const string ZERO_DATE = '0000-00-00 00:00:00';

    private const string TOKENS = "/'(?:[^'\\\\]++|\\\\.|'')*+'|NULL|-?\\d+(?:\\.\\d+)?(?:[eE][-+]?\\d+)?|[()]/s";

    /**
     * @param  list<string>  $columns
     * @return list<array<string, mixed>>
     */
    public static function rows(array $columns, string $values): array
    {
        preg_match_all(self::TOKENS, $values, $tokens);

        $rows = [];
        $row = null;

        foreach ($tokens[0] as $token) {
            if ($token === '(') {
                $row = [];
            } elseif ($token === ')') {
                $rows[] = self::combine($columns, $row);
                $row = null;
            } elseif ($row !== null) {
                $row[] = self::value($token);
            }
        }

        return $rows;
    }

    /**
     * @param  list<string>  $columns
     * @param  list<mixed>|null  $row
     * @return array<string, mixed>
     */
    private static function combine(array $columns, ?array $row): array
    {
        if ($row === null || count($row) !== count($columns)) {
            throw new RuntimeException('Could not read a row of the dump: column count does not match.');
        }

        return array_combine($columns, $row);
    }

    private static function value(string $token): mixed
    {
        if ($token === 'NULL') {
            return null;
        }

        if ($token[0] !== "'") {
            return str_contains($token, '.') || stripos($token, 'e') !== false ? (float) $token : (int) $token;
        }

        // One pass, left to right: a backslash escape, or a doubled quote (group 1 unset).
        $value = preg_replace_callback(
            "/\\\\(.)|''/s",
            fn (array $m): string => match ($m[1]) {
                null => "'",
                '0' => "\0",
                'b' => "\x08",
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'Z' => "\x1a",
                default => $m[1],
            },
            substr($token, 1, -1),
            flags: PREG_UNMATCHED_AS_NULL,
        );

        // MySQL's "no date" placeholder is not a date anywhere else.
        return $value === self::ZERO_DATE ? null : $value;
    }
}
