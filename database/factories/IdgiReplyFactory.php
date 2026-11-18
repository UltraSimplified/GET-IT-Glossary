<?php

namespace Database\Factories;

use App\Models\IdgiReply;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdgiReply>
 */
class IdgiReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'term_id' => Term::factory(),
            'master_id' => fn (array $attributes): int => Term::query()->where('id', $attributes['term_id'])->firstOrFail()->master_id,
            'message' => fake()->sentence(),
        ];
    }

    /**
     * Marked dealt with by an editor.
     */
    public function handled(?User $by = null): static
    {
        return $this->state(fn (): array => [
            'handled_at' => now(),
            'handled_by' => $by !== null ? $by->id : User::factory(),
        ]);
    }
}
