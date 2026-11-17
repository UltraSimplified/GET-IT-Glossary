<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A log entry for each lookup or search made on the public glossary.
 *
 * @property int $id
 * @property string $search
 * @property string|null $term
 * @property int|null $term_id
 * @property string|null $ip_address
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['search', 'term', 'term_id', 'ip_address'])]
class Search extends Model
{
    public const string NO_MATCH = 'NO MATCH';

    /**
     * Record what a visitor looked for and which term (if any) it led to.
     */
    public static function record(string $search, ?Term $term = null, ?string $ip = null, ?string $fallback = null): self
    {
        return self::query()->create([
            'search' => mb_substr($search, 0, 255),
            'term' => $term->term ?? $fallback,
            'term_id' => $term?->id,
            'ip_address' => $ip,
        ]);
    }
}
