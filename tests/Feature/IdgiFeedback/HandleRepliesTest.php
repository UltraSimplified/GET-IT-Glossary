<?php

namespace Tests\Feature\IdgiFeedback;

use App\Models\IdgiReply;
use App\Models\Term;
use App\Models\TermEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandleRepliesTest extends TestCase
{
    use RefreshDatabase;

    private function editorOf(Term $term): User
    {
        $editor = User::factory()->create();
        TermEditor::query()->create(['term_id' => $term->master_id, 'user_id' => $editor->id]);

        return $editor;
    }

    public function test_an_editor_marks_a_reply_dealt_with_and_new_again(): void
    {
        $reply = IdgiReply::factory()->create();
        $editor = $this->editorOf($reply->term);

        $this->actingAs($editor)
            ->from("/terms/{$reply->term_id}/edit")
            ->patch("/idgi-replies/{$reply->id}", ['handled' => true])
            ->assertRedirect("/terms/{$reply->term_id}/edit");

        $reply->refresh();
        $this->assertNotNull($reply->handled_at);
        $this->assertSame($editor->id, $reply->handled_by);

        $this->actingAs($editor)->patch("/idgi-replies/{$reply->id}", ['handled' => false]);

        $reply->refresh();
        $this->assertNull($reply->handled_at);
        $this->assertNull($reply->handled_by);
    }

    public function test_someone_who_is_not_an_editor_of_the_term_is_refused(): void
    {
        $reply = IdgiReply::factory()->create();
        $this->editorOf(Term::factory()->create());

        $this->actingAs(User::factory()->create())
            ->patch("/idgi-replies/{$reply->id}", ['handled' => true])
            ->assertForbidden();

        $this->assertNull($reply->fresh()?->handled_at);
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $reply = IdgiReply::factory()->create();

        $this->patch("/idgi-replies/{$reply->id}", ['handled' => true])->assertRedirect('/login');
        $this->delete("/idgi-replies/{$reply->id}")->assertRedirect('/login');
        $this->assertModelExists($reply);
    }

    public function test_an_editor_who_is_not_an_administrator_cannot_delete(): void
    {
        $reply = IdgiReply::factory()->create();

        $this->actingAs($this->editorOf($reply->term))
            ->delete("/idgi-replies/{$reply->id}")
            ->assertForbidden();

        $this->assertModelExists($reply);
    }

    public function test_an_administrator_deletes_a_reply(): void
    {
        $reply = IdgiReply::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from("/terms/{$reply->term_id}/edit")
            ->delete("/idgi-replies/{$reply->id}")
            ->assertRedirect("/terms/{$reply->term_id}/edit");

        $this->assertModelMissing($reply);
    }
}
