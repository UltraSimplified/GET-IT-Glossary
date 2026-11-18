<?php

namespace Tests\Feature\IdgiFeedback;

use App\Models\IdgiReply;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RepliesOutlastVersionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return [
            'term' => 'placebo',
            'term_ordered' => '',
            'term_status' => Term::STATUS_LIVE,
            'do_not_use' => false,
            'tags' => [],
            'definition' => 'A dummy [treatment].',
            ...$overrides,
        ];
    }

    public function test_saving_a_new_version_keeps_the_replies_on_the_earlier_one(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $term = Term::factory()->create(['term' => 'placebo']);
        $replies = IdgiReply::factory()->count(2)->for($term)->create();

        $this->actingAs($admin)
            ->put("/terms/{$term->id}", $this->form(['definition' => 'Reworded.', 'mode' => 'version']))
            ->assertSessionHasNoErrors();

        $newer = Term::query()->where('master_id', $term->master_id)->where('version', 2)->sole();

        foreach ($replies as $reply) {
            $this->assertSame($term->id, $reply->fresh()?->term_id);
        }

        $this->actingAs($admin)
            ->get("/terms/{$newer->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->has('replies', 2)
                ->where('replies.0.version', 1)
                ->where('replies.0.earlier', true)
                ->where('replies.1.earlier', true));
    }

    public function test_updating_a_version_leaves_its_replies_in_place(): void
    {
        $term = Term::factory()->create(['term' => 'placebo']);
        $reply = IdgiReply::factory()->for($term)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put("/terms/{$term->id}", $this->form(['definition' => 'Corrected.', 'mode' => 'update']))
            ->assertSessionHasNoErrors();

        $this->assertSame($term->id, $reply->fresh()?->term_id);
        $this->assertSame(1, IdgiReply::query()->count());
    }

    public function test_rolling_back_leaves_the_replies_in_place(): void
    {
        Mail::fake();
        $first = Term::factory()->revision()->create(['term' => 'placebo', 'version' => 1]);
        $current = Term::factory()->create(['term' => 'placebo', 'version' => 2, 'master_id' => $first->id]);
        $onFirst = IdgiReply::factory()->for($first)->create();
        $onCurrent = IdgiReply::factory()->for($current)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/terms/{$first->id}/rollback")
            ->assertRedirect('/terms');

        $this->assertSame($first->id, $onFirst->fresh()?->term_id);
        $this->assertSame($current->id, $onCurrent->fresh()?->term_id);
        $this->assertSame(2, IdgiReply::query()->where('master_id', $first->id)->count());
    }
}
