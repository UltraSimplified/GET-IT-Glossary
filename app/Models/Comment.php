<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An editorial comment left on one version of a term by a signed-in user.
 *
 * @property int $id
 * @property int $term_id
 * @property string $comment
 * @property int $author_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $author
 * @property-read Term|null $term
 */
#[Fillable(['term_id', 'comment', 'author_id'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        // Comments outlive the people who wrote them.
        return $this->belongsTo(User::class, 'author_id')
            ->withDefault(fn (User $user) => $user->forceFill(['first_name' => __('Former user'), 'last_name' => '']));
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
