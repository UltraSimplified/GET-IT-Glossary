<?php

namespace Tests\Feature\Admin;

use App\Mail\NewTermEditor;
use App\Mail\TermChanged;
use App\Models\Term;
use App\Models\TermEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TermManagementTest extends TestCase
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
            'term_ordered' => 'placebo',
            'term_status' => Term::STATUS_LIVE,
            'do_not_use' => false,
            'tags' => ['testing treatments'],
            'definition' => 'A dummy [treatment].',
            'explanation' => 'Looks like the real thing.',
            ...$overrides,
        ];
    }

    private function editorOf(Term $term): User
    {
        $editor = User::factory()->create();
        TermEditor::query()->create(['term_id' => $term->master_id, 'user_id' => $editor->id]);

        return $editor;
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get('/terms')->assertRedirect('/login');
        $this->get('/users')->assertRedirect('/login');
        $this->get('/pages')->assertRedirect('/login');
    }

    public function test_registering_does_not_open_the_management_screens(): void
    {
        $term = Term::factory()->create();
        $contributor = User::factory()->create();

        $this->actingAs($contributor);

        $this->get('/users')->assertForbidden();
        $this->get('/pages')->assertForbidden();
        $this->get('/terms/create')->assertForbidden();
        $this->post('/terms', $this->form())->assertForbidden();
        $this->get("/terms/{$term->id}/edit")->assertRedirect('/terms');
        $this->put("/terms/{$term->id}", $this->form(['term' => 'hijacked']))->assertRedirect('/terms');

        $this->assertDatabaseMissing('terms', ['term' => 'hijacked']);
    }

    public function test_editors_see_only_their_terms_and_admins_see_all_current_ones(): void
    {
        $mine = Term::factory()->create(['term' => 'bias']);
        Term::factory()->create(['term' => 'placebo']);
        Term::factory()->revision()->create(['term' => 'bias', 'master_id' => $mine->id]);
        Term::factory()->draft()->create(['term' => 'draft term']);

        $this->actingAs($this->editorOf($mine))->get('/terms')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/terms/Index')
                ->where('canCreate', false)
                ->has('terms', 1)
                ->where('terms.0.term', 'bias'));

        $this->actingAs(User::factory()->admin()->create())->get('/terms')
            ->assertInertia(fn (Assert $page) => $page
                ->where('canCreate', true)
                ->has('terms', 3));
    }

    public function test_an_admin_can_create_a_term_and_it_becomes_its_own_master(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/terms', $this->form())->assertRedirect('/terms');

        $term = Term::query()->where('term', 'placebo')->firstOrFail();

        $this->assertSame($term->id, $term->master_id);
        $this->assertSame(1, $term->version);
        $this->assertTrue($term->enabled);
        $this->assertSame('testing treatments', $term->tags);
        $this->assertSame($admin->id, $term->updated_by);
    }

    public function test_only_live_terms_are_published(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/terms', $this->form(['term_status' => Term::STATUS_DRAFT]));

        $this->assertFalse(Term::query()->where('term', 'placebo')->firstOrFail()->enabled);
        $this->get('/term/placebo')->assertNotFound();
    }

    public function test_updating_this_version_changes_the_row_in_place(): void
    {
        Mail::fake();
        $term = Term::factory()->create(['term' => 'placebo', 'hits' => 12]);

        $this->actingAs($this->editorOf($term))
            ->put("/terms/{$term->id}", $this->form(['definition' => 'Reworded.', 'mode' => 'update']))
            ->assertRedirect('/term/placebo');

        $this->assertSame(1, Term::query()->count());
        $term->refresh();
        $this->assertSame('Reworded.', $term->definition);
        $this->assertSame(1, $term->version);
        $this->assertSame(12, $term->hits);
        Mail::assertNothingSent();
    }

    public function test_saving_a_new_version_keeps_the_old_one_as_history_and_tells_the_admins(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $term = Term::factory()->create(['term' => 'placebo', 'hits' => 12, 'IDGI' => 5]);
        $editor = $this->editorOf($term);

        $this->actingAs($editor)
            ->put("/terms/{$term->id}", $this->form(['definition' => 'Second wording.', 'mode' => 'version']))
            ->assertRedirect('/term/placebo');

        $term->refresh();
        $this->assertSame(Term::STATUS_REVISION, $term->term_status);
        $this->assertFalse($term->enabled);

        $latest = Term::query()->where('master_id', $term->master_id)->where('version', 2)->firstOrFail();
        $this->assertSame('Second wording.', $latest->definition);
        $this->assertTrue($latest->enabled);
        $this->assertSame(0, $latest->hits);
        $this->assertSame(0, $latest->IDGI);
        $this->assertSame($editor->id, $latest->updated_by);

        Mail::assertSent(TermChanged::class, fn (TermChanged $mail) => $mail->hasTo($admin->email) && ! $mail->rolledBack);

        // The editor keeps their rights on the new version.
        $this->actingAs($editor)->get("/terms/{$latest->id}/edit")->assertOk();
        // The old version's edit link leads to the current one.
        $this->actingAs($editor)->get("/terms/{$term->id}/edit")->assertRedirect("/terms/{$latest->id}/edit");
    }

    public function test_an_admin_assigns_editors_and_new_ones_are_emailed_at_their_own_address(): void
    {
        Mail::fake();
        $term = Term::factory()->create(['term' => 'placebo']);
        $existing = $this->editorOf($term);
        $added = User::factory()->create(['email' => 'new.editor@example.com']);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/terms/{$term->id}", $this->form(['mode' => 'update', 'editors' => [$added->id]]));

        $this->assertDatabaseHas('terms_editors', ['term_id' => $term->master_id, 'user_id' => $added->id]);
        $this->assertDatabaseMissing('terms_editors', ['term_id' => $term->master_id, 'user_id' => $existing->id]);

        Mail::assertSent(NewTermEditor::class, 1);
        Mail::assertSent(NewTermEditor::class, fn (NewTermEditor $mail) => $mail->hasTo('new.editor@example.com'));
    }

    public function test_editors_cannot_change_who_edits_a_term(): void
    {
        Mail::fake();
        $term = Term::factory()->create(['term' => 'placebo']);
        $editor = $this->editorOf($term);
        $friend = User::factory()->create();

        $this->actingAs($editor)
            ->put("/terms/{$term->id}", $this->form(['mode' => 'update', 'editors' => [$friend->id]]));

        $this->assertDatabaseMissing('terms_editors', ['user_id' => $friend->id]);
        $this->assertDatabaseHas('terms_editors', ['user_id' => $editor->id]);
    }

    public function test_versions_can_be_compared_and_rolled_back(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $first = Term::factory()->revision()->create(['term' => 'bias', 'definition' => 'First wording.', 'version' => 1]);
        $current = Term::factory()->create([
            'term' => 'bias', 'definition' => 'Current wording.', 'version' => 2, 'master_id' => $first->id,
        ]);

        $this->actingAs($admin)->get("/terms/{$first->id}/version/1")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/terms/Compare')
                ->where('selected.definition_html', 'First wording.')
                ->where('current.definition_html', 'Current wording.')
                ->where('versions', [1]));

        $this->actingAs($admin)->post("/terms/{$first->id}/rollback")->assertRedirect('/terms');

        $restored = Term::query()->where('master_id', $first->id)->where('version', 3)->firstOrFail();
        $this->assertSame('First wording.', $restored->definition);
        $this->assertTrue($restored->enabled);
        $this->assertSame(Term::STATUS_LIVE, $restored->term_status);
        $this->assertSame(Term::STATUS_REVISION, $current->fresh()->term_status);

        Mail::assertSent(TermChanged::class, fn (TermChanged $mail) => $mail->rolledBack);
    }

    public function test_the_term_form_is_validated(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/terms', $this->form(['term' => '', 'definition' => '', 'term_status' => 'REVISION', 'tags' => ['made up']]))
            ->assertSessionHasErrors(['term', 'definition', 'term_status', 'tags.0']);
    }
}
