<?php

namespace App\Services\LegacyImport;

use Illuminate\Support\Facades\Schema;

/**
 * Turns rows from the legacy database into rows for the new schema.
 */
final class LegacyRowMapper
{
    /** @var array<string, array<string, int>> Column names of each target table. */
    private array $columns = [];

    /**
     * Rows the old site had soft-deleted are left out.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function map(string $table, array $rows): array
    {
        $live = array_filter($rows, fn (array $row): bool => blank($row['deleted_at'] ?? null));

        return array_values(array_map(fn (array $row): array => $this->mapRow($table, $row), $live));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRow(string $table, array $row): array
    {
        return match ($table) {
            'users' => $this->user($row),
            'terms' => $this->term($row),
            'pages' => $this->page($row),
            'searches' => $this->search($row),
            default => $this->onlyColumns($table, $row),
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function user(array $row): array
    {
        return [
            'id' => $row['id'],
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'username' => blank($row['username'] ?? null) ? null : $row['username'],
            // Sign-in lower-cases the address typed, so stored addresses must match.
            'email' => mb_strtolower(trim((string) $row['email'])),
            'password' => $row['password'],
            'is_admin' => (bool) ($row['is_admin'] ?? false),
            'enabled' => (bool) ($row['enabled'] ?? true),
            'remember_token' => blank($row['remember_token'] ?? null) ? null : $row['remember_token'],
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function term(array $row): array
    {
        $row = $this->onlyColumns('terms', $row);

        $row['do_not_use'] = (bool) ($row['do_not_use'] ?? false);
        $row['enabled'] = (bool) ($row['enabled'] ?? false);
        $row['term_ordered'] ??= '';
        $row['tags'] ??= '';
        $row['master_id'] = (int) ($row['master_id'] ?? 0);
        $row['updated_by'] = $this->userId($row['updated_by'] ?? null);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function page(array $row): array
    {
        $row = $this->onlyColumns('pages', $row);
        $row['slug'] ??= '';
        $row['updated_by'] = $this->userId($row['updated_by'] ?? null);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function search(array $row): array
    {
        $row = $this->onlyColumns('searches', $row);
        $row['term_id'] = ($row['term_id'] ?? 0) ?: null;

        return $row;
    }

    /**
     * The old schema used 0 for "nobody".
     */
    private function userId(mixed $id): ?int
    {
        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    /**
     * Drop dump columns the new schema no longer has.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function onlyColumns(string $table, array $row): array
    {
        $this->columns[$table] ??= array_flip(Schema::getColumnListing($table));

        return array_intersect_key($row, $this->columns[$table]);
    }
}
