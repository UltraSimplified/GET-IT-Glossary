<?php

namespace App\Services;

use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Searches published terms and ranks them: a hit in the term name counts
 * for most, then common variants, synonyms, the definition and finally the
 * full explanation.
 */
class TermSearch
{
    /** @var array<string, int> */
    private const array WEIGHTS = [
        'term' => 20,
        'common_variants' => 18,
        'synonyms' => 10,
        'definition' => 5,
        'explanation' => 2,
    ];

    /**
     * @return Collection<int, Term>
     */
    public function search(string $query): Collection
    {
        $words = $this->words($query);

        if ($words === []) {
            return new Collection;
        }

        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? $this->fullText($words)
            : $this->like($words);
    }

    /**
     * Split a query into search words, dropping the operators MySQL's
     * boolean mode would otherwise try to interpret.
     *
     * @return list<string>
     */
    private function words(string $query): array
    {
        $clean = preg_replace('/[+\-<>()~*"@%_\\\\]+/u', ' ', mb_strtolower($query)) ?? '';

        return array_values(array_filter(preg_split('/\s+/u', trim($clean)) ?: []));
    }

    /**
     * MySQL / MariaDB: weighted FULLTEXT search, matching word prefixes.
     *
     * @param  list<string>  $words
     * @return Collection<int, Term>
     */
    private function fullText(array $words): Collection
    {
        $against = implode(' ', $words).'*';

        $relevance = '0';

        foreach (self::WEIGHTS as $column => $weight) {
            $relevance .= " + ({$weight} * (MATCH(`{$column}`) AGAINST (? IN BOOLEAN MODE)))";
        }

        return Term::query()
            ->select('terms.*')
            ->selectRaw("({$relevance}) AS relevance", array_fill(0, count(self::WEIGHTS), $against))
            ->live()
            ->whereRaw(
                'MATCH(`term`,`common_variants`,`synonyms`,`definition`,`explanation`) AGAINST (? IN BOOLEAN MODE)',
                [$against],
            )
            ->orderByDesc('relevance')
            ->get();
    }

    /**
     * Any other database (SQLite in development): substring search with
     * the same weighting applied in PHP.
     *
     * @param  list<string>  $words
     * @return Collection<int, Term>
     */
    private function like(array $words): Collection
    {
        $columns = array_keys(self::WEIGHTS);

        $terms = Term::query()
            ->live()
            ->where(function (Builder $query) use ($words, $columns): void {
                foreach ($words as $word) {
                    foreach ($columns as $column) {
                        $query->orWhere($column, 'LIKE', '%'.$word.'%');
                    }
                }
            })
            ->get();

        return $terms
            ->each(function (Term $term) use ($words): void {
                $score = 0;

                foreach ($words as $word) {
                    foreach (self::WEIGHTS as $column => $weight) {
                        if (mb_stripos(strip_tags((string) $term->{$column}), $word) !== false) {
                            $score += $weight;
                        }
                    }
                }

                $term->setAttribute('relevance', $score);
            })
            ->sortBy([['relevance', 'desc'], ['term', 'asc']])
            ->values();
    }

    /**
     * Wrap each occurrence of the search words in <mark>, leaving HTML tags
     * and entities untouched.
     */
    public function highlight(string $html, string $query): string
    {
        $words = array_map(fn (string $word): string => preg_quote($word, '/'), $this->words($query));

        if ($words === [] || $html === '') {
            return $html;
        }

        $pattern = '/('.implode('|', $words).')/iu';
        $chunks = preg_split('/(<[^>]*>|&[#\w]+;)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($chunks === false) {
            return $html;
        }

        // Odd chunks are the captured tags and entities; even chunks are text.
        foreach ($chunks as $index => $chunk) {
            if ($index % 2 === 0 && $chunk !== '') {
                $chunks[$index] = preg_replace($pattern, '<mark>$1</mark>', $chunk) ?? $chunk;
            }
        }

        return implode('', $chunks);
    }
}
