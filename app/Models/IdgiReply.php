<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\IdgiReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a reader said after pressing "I don't get it" on a term. Anonymous:
 * it keeps the words, the version they were reading and the date only.
 *
 * @property int $id
 * @property int $term_id The version the reader was looking at.
 * @property int $master_id The term as a whole.
 * @property string $message
 * @property CarbonImmutable|null $handled_at When an editor marked it dealt with.
 * @property int|null $handled_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Term $term
 * @property-read User|null $handler
 */
#[Fillable(['term_id', 'master_id', 'message', 'handled_at', 'handled_by'])]
class IdgiReply extends Model
{
    /** @use HasFactory<IdgiReplyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Who marked the reply dealt with.
     *
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
