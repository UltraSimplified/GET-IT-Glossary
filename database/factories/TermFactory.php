<?php

namespace Database\Factories;

use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $term = fake()->unique()->words(2, true);

        return [
            'term' => $term,
            // Only set when a term is filed under another word ("risk, absolute").
            'term_ordered' => '',
            'term_status' => Term::STATUS_LIVE,
            'enabled' => true,
            'version' => 1,
            'tags' => '',
            'definition' => fake()->sentence(),
            'explanation' => fake()->paragraph(),
            'hits' => 0,
            'IDGI' => 0,
        ];
    }

    /**
     * Give each new term its own master_id, as the first version of a term has.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Term $term): void {
            if (! $term->master_id) {
                $term->forceFill(['master_id' => $term->id])->saveQuietly();
            }
        });
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'term_status' => Term::STATUS_DRAFT,
            'enabled' => false,
        ]);
    }

    /**
     * An earlier version that a newer one has replaced.
     */
    public function revision(): static
    {
        return $this->state(fn (array $attributes) => [
            'term_status' => Term::STATUS_REVISION,
            'enabled' => false,
        ]);
    }
}
