<?php

namespace Tests\Feature\IdgiFeedback;

use App\Models\IdgiReply;
use App\Models\Term;
use App\Models\TermEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EditorRepliesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A term at version 3, with versions 1 and 2 kept as history.
     *
     * @return array{0: Term, 1: Term, 2: Term}
     */
    private function termWithHistory(): array
    {
        $first = Term::factory()->revision()->create(['term' => 'placebo']);
        $second = Term::factory()->revision()->create(['term' => 'placebo', 'master_id' => $first->master_id, 'version' => 2]);
        $current = Term::factory()->create(['term' => 'placebo', 'master_id' => $first->master_id, 'version' => 3]);

        return [$first, $second, $current];
    }

    private function editorOf(Term $term): User
    {
        $editor = User::factory()->create();
        TermEditor::query()->create(['term_id' => $term->master_id, 'user_id' => $editor->id]);

        return $editor;
    }

    public function test_the_edit_screen_lists_new_replies_first_then_those_dealt_with(): void
    {
        [, $second, $current] = $this->termWithHistory();
        $handler = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Editor']);

        $handled = IdgiReply::factory()->for($current)->handled($handler)->create(['message' => 'Handled', 'created_at' => now()->subMinute()]);
        $older = IdgiReply::factory()->for($second)->create(['message' => 'Older new', 'created_at' => now()->subDays(2)]);
        $newer = IdgiReply::factory()->for($current)->create(['message' => 'Newer new', 'created_at' => now()->subHour()]);
        IdgiReply::factory()->create(['message' => 'Another term']);

        $this->actingAs($this->editorOf($current))
            ->get("/terms/{$current->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/terms/Edit')
                ->has('replies', 3)
                ->where('replies.0.id', $newer->id)
                ->where('replies.0.message', 'Newer new')
                ->where('replies.0.version', 3)
                ->where('replies.0.earlier', false)
                ->where('replies.0.handled', null)
                ->where('replies.1.id', $older->id)
                ->where('replies.1.version', 2)
                ->where('replies.1.earlier', true)
                ->where('replies.2.id', $handled->id)
                ->where('replies.2.handled.by', 'Ada Editor')
                ->has('replies.2.handled.date'));
    }

    public function test_someone_who_is_not_an_editor_of_the_term_is_still_refused(): void
    {
        [, , $current] = $this->termWithHistory();
        IdgiReply::factory()->for($current)->create(['message' => 'Private']);

        $this->actingAs(User::factory()->create())
            ->get("/terms/{$current->id}/edit")
            ->assertRedirect('/terms');
    }
}
