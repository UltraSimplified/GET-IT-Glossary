<?php

namespace Tests\Feature\IdgiFeedback;

use App\Models\IdgiReply;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReaderReplyTest extends TestCase
{
    use RefreshDatabase;

    private function click(Term $term): void
    {
        $this->postJson("/idgi/{$term->id}")->assertOk();
    }

    public function test_a_reply_after_a_click_is_stored_with_its_version(): void
    {
        $first = Term::factory()->revision()->create();
        $term = Term::factory()->create(['master_id' => $first->master_id, 'version' => 3, 'IDGI' => 4]);
        $editedAt = $term->fresh()?->updated_at;

        $this->click($term);
        $this->postJson("/idgi/{$term->id}/reply", ['message' => '  What does allocation concealment mean here?  '])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $reply = IdgiReply::query()->sole();
        $this->assertSame('What does allocation concealment mean here?', $reply->message);
        $this->assertSame($term->id, $reply->term_id);
        $this->assertSame($first->master_id, $reply->master_id);
        $this->assertNotNull($reply->created_at);
        $this->assertNull($reply->handled_at);

        // The click itself is counted as before.
        $term->refresh();
        $this->assertSame(5, $term->IDGI);
        $this->assertEquals($editedAt, $term->updated_at);
    }

    public function test_a_reply_keeps_nothing_about_the_visitor(): void
    {
        $this->assertSame(
            ['created_at', 'handled_at', 'handled_by', 'id', 'master_id', 'message', 'term_id', 'updated_at'],
            collect(Schema::getColumnListing('idgi_replies'))->sort()->values()->all(),
        );
    }

    public function test_a_reply_without_a_click_is_refused(): void
    {
        $term = Term::factory()->create();

        $this->postJson("/idgi/{$term->id}/reply", ['message' => 'Unclear.'])->assertForbidden();

        $this->assertSame(0, IdgiReply::query()->count());
    }

    public function test_a_click_on_another_term_does_not_count(): void
    {
        $clicked = Term::factory()->create();
        $other = Term::factory()->create();

        $this->click($clicked);
        $this->postJson("/idgi/{$other->id}/reply", ['message' => 'Unclear.'])->assertForbidden();

        $this->assertSame(0, IdgiReply::query()->count());
    }

    public function test_one_click_allows_one_reply(): void
    {
        $term = Term::factory()->create();

        $this->click($term);
        $this->postJson("/idgi/{$term->id}/reply", ['message' => 'First.'])->assertOk();
        $this->postJson("/idgi/{$term->id}/reply", ['message' => 'Second.'])->assertForbidden();

        $this->assertSame(['First.'], IdgiReply::query()->pluck('message')->all());
    }

    public function test_an_empty_reply_is_refused_and_the_click_still_allows_one(): void
    {
        $term = Term::factory()->create();

        $this->click($term);
        $this->postJson("/idgi/{$term->id}/reply", ['message' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message' => 'Please say what didn’t make sense before sending.']);

        $this->assertSame(0, IdgiReply::query()->count());

        // Having fixed it, the reader can still send it.
        $this->postJson("/idgi/{$term->id}/reply", ['message' => 'Unclear.'])->assertOk();
    }

    public function test_an_over_long_reply_is_refused(): void
    {
        $term = Term::factory()->create();

        $this->click($term);
        $this->postJson("/idgi/{$term->id}/reply", ['message' => Str::repeat('a', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message' => 'Please keep it to 1,000 characters or fewer.']);

        $this->postJson("/idgi/{$term->id}/reply", ['message' => Str::repeat('a', 1000)])->assertOk();
        $this->assertSame(1, IdgiReply::query()->count());
    }

    public function test_the_hidden_field_discards_the_reply_but_looks_like_success(): void
    {
        $term = Term::factory()->create();

        $this->click($term);
        $this->postJson("/idgi/{$term->id}/reply", ['message' => 'Buy now', 'website' => 'https://spam.example'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(0, IdgiReply::query()->count());
    }

    public function test_unpublished_versions_take_no_replies(): void
    {
        foreach ([Term::factory()->draft()->create(), Term::factory()->revision()->create(), Term::factory()->create(['term_status' => Term::STATUS_ARCHIVED, 'enabled' => false])] as $term) {
            $this->click($term);
            $this->postJson("/idgi/{$term->id}/reply", ['message' => 'Unclear.'])->assertNotFound();
        }

        $this->assertSame(0, IdgiReply::query()->count());
    }

    public function test_replies_are_limited_to_six_a_minute(): void
    {
        $term = Term::factory()->create();

        foreach (range(1, 6) as $attempt) {
            $this->postJson("/idgi/{$term->id}/reply", ['message' => 'Unclear.'])->assertForbidden();
        }

        $this->postJson("/idgi/{$term->id}/reply", ['message' => 'Unclear.'])->assertTooManyRequests();
    }
}
