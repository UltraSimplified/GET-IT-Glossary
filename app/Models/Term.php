<?php

namespace App\Models;

use App\Support\Glossary;
use App\Support\TermLinker;
use Carbon\CarbonImmutable;
use Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One saved version of a glossary term.
 *
 * Saving a term "as a new version" inserts a new row and demotes the previous
 * row to the REVISION status, so the history of a term is every row sharing
 * its master_id.
 *
 * @property int $id
 * @property int $master_id
 * @property string $term
 * @property string $term_ordered
 * @property bool $do_not_use
 * @property string|null $warning
 * @property string $term_status
 * @property string $tags
 * @property int $version
 * @property string|null $synonyms
 * @property string|null $see_also
 * @property string|null $links
 * @property string|null $definition
 * @property string|null $explanation
 * @property string|null $common_variants
 * @property string|null $audio_clip Old SoundCloud embed code, no longer shown.
 * @property string|null $pronunciation Public path of a recording of the term said aloud.
 * @property string|null $video
 * @property string|null $test
 * @property string|null $cartoon
 * @property string|null $example
 * @property int $hits
 * @property int $IDGI
 * @property bool $enabled
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $updater
 */
#[Fillable([
    'master_id', 'term', 'term_ordered', 'term_status', 'do_not_use', 'warning',
    'tags', 'see_also', 'links', 'synonyms', 'definition', 'explanation',
    'common_variants', 'audio_clip', 'pronunciation', 'video', 'test', 'cartoon', 'example',
    'hits', 'IDGI', 'enabled', 'version', 'updated_by',
])]
class Term extends Model
{
    /** @use HasFactory<TermFactory> */
    use HasFactory;

    public const string STATUS_DRAFT = 'DRAFT';

    public const string STATUS_LIVE = 'LIVE';

    public const string STATUS_ARCHIVED = 'ARCHIVED';

    public const string STATUS_REVISION = 'REVISION';

    /**
     * The statuses an editor can choose. REVISION is set by the system when
     * a newer version replaces a row.
     *
     * @var list<string>
     */
    public const array EDITABLE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_LIVE,
        self::STATUS_ARCHIVED,
    ];

    protected static function booted(): void
    {
        // The A–Z index remembers which letters have terms.
        static::saved(fn () => Glossary::forgetLetters());
        static::deleted(fn () => Glossary::forgetLetters());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'do_not_use' => 'boolean',
            'enabled' => 'boolean',
            'version' => 'integer',
            'hits' => 'integer',
            'IDGI' => 'integer',
            'master_id' => 'integer',
        ];
    }

    /**
     * Terms that are published on the public glossary.
     *
     * @param  Builder<Term>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->where('enabled', true);
    }

    /**
     * The newest version of each term, whatever its status.
     *
     * @param  Builder<Term>  $query
     */
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->where('term_status', '!=', self::STATUS_REVISION);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * The users allowed to edit this term (keyed on master_id, so the
     * assignment carries across versions).
     *
     * @return BelongsToMany<User, $this>
     */
    public function editors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'terms_editors', 'term_id', 'user_id', 'master_id', 'id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Every saved version of this term, oldest first.
     *
     * @return HasMany<Term, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'master_id', 'master_id')
            ->orderBy('version')
            ->orderBy('id');
    }

    /**
     * Find the published term a visitor asked for.
     *
     * Tries an exact match first, then falls back to the alphabetised name,
     * the synonyms and finally the common variants.
     */
    public static function findPublished(string $query): ?self
    {
        $query = mb_strtolower(trim($query));

        if ($query === '') {
            return null;
        }

        $exact = self::query()->live()
            ->where(fn (Builder $q) => $q->where('term', $query)->orWhereRaw('LOWER(term) = ?', [$query]))
            ->first();

        if ($exact) {
            return $exact;
        }

        $like = '%'.$query.'%';

        foreach (['term_ordered', 'synonyms', 'common_variants'] as $column) {
            $match = self::query()->live()->where($column, 'LIKE', $like)->first();

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    /**
     * The public URL path of this term.
     */
    public function path(): string
    {
        return TermLinker::path($this->term);
    }

    /**
     * Share of views where the reader clicked "I don't get it", as a
     * whole percentage. Null until there is something to measure.
     */
    public function idgiPercent(): ?int
    {
        if ($this->hits <= 0 || $this->IDGI <= 0) {
            return null;
        }

        return (int) round($this->IDGI / $this->hits * 100);
    }

    /**
     * Count a public view without touching updated_at, which records the
     * last editorial change.
     */
    public function recordHit(): int
    {
        self::query()->toBase()->where('id', $this->id)->increment('hits');

        return $this->hits = $this->hits + 1;
    }
}
