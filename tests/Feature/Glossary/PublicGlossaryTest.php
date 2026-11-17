<?php

namespace Tests\Feature\Glossary;

use App\Mail\ContactMessage;
use App\Models\Page;
use App\Models\Search;
use App\Models\Site;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicGlossaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_shares_the_letters_that_have_terms(): void
    {
        Term::factory()->create(['term' => 'bias']);
        Term::factory()->create(['term' => 'randomised trial', 'term_ordered' => 'trial, randomised']);
        Term::factory()->draft()->create(['term' => 'zebra']);
        Site::query()->sole()->update(['name' => 'AIM Glossary']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('glossary.title', 'AIM Glossary')
                ->where('glossary.letters', ['B', 'R', 'T'])
                ->has('glossary.footer', 3));
    }

    public function test_a_listing_shows_published_terms_filed_under_the_letter(): void
    {
        Term::factory()->create(['term' => 'risk', 'definition' => 'The chance of [harm].']);
        Term::factory()->create(['term' => 'absolute risk reduction', 'term_ordered' => 'risk reduction, absolute']);
        Term::factory()->create(['term' => 'bias']);
        Term::factory()->draft()->create(['term' => 'rate']);

        $this->get('/listing/r')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listing')
                ->where('letter', 'R')
                ->has('terms', 2)
                ->where('terms.0.term', 'risk')
                ->where('terms.0.listed_as', null)
                ->where('terms.0.definition_html', 'The chance of harm.')
                ->where('terms.1.term', 'absolute risk reduction')
                ->where('terms.1.listed_as', 'risk reduction, absolute'));
    }

    public function test_a_term_page_links_references_counts_the_view_and_logs_the_lookup(): void
    {
        $term = Term::factory()->create([
            'term' => 'randomised trial',
            'definition' => 'A [study] where chance decides.',
            'synonyms' => 'RCT; !!random test',
            'see_also' => 'bias; fair comparison',
            'hits' => 4,
        ]);
        $editedAt = $term->fresh()->updated_at;
        Term::factory()->create(['term' => 'placebo']);
        Term::factory()->create(['term' => 'study']);

        $this->get('/term/randomised+trial')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Term')
                ->where('term.term', 'randomised trial')
                ->where('term.definition_html', 'A <a href="/term/study">study</a> where chance decides.')
                ->where('term.see_also.1.path', '/term/fair+comparison')
                ->where('letter', 'R')
                ->where('adjacent.previous.term', 'placebo')
                ->where('adjacent.next.term', 'study')
                ->where('comments', null)
                ->where('canEdit', false));

        $term->refresh();
        $this->assertSame(5, $term->hits);
        $this->assertEquals($editedAt, $term->updated_at, 'Counting a view must not look like an edit.');
        $this->assertDatabaseHas('searches', ['search' => 'randomised trial', 'term_id' => $term->id]);
    }

    public function test_a_term_can_be_found_by_synonym_or_common_variant(): void
    {
        Term::factory()->create(['term' => 'randomised trial', 'synonyms' => 'RCT', 'common_variants' => 'randomized trial']);

        foreach (['/term/rct', '/term/randomized+trial', '/term/Randomised%20Trial'] as $url) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('term.term', 'randomised trial'));
        }
    }

    public function test_an_unknown_or_unpublished_term_is_a_not_found_page_and_is_logged(): void
    {
        Term::factory()->draft()->create(['term' => 'surrogate outcome']);

        $this->get('/term/surrogate+outcome')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Term')
                ->where('term', null)
                ->where('query', 'surrogate outcome'));

        $this->assertDatabaseHas('searches', ['search' => 'surrogate outcome', 'term_id' => null]);
    }

    public function test_search_ranks_name_matches_first_and_highlights_them(): void
    {
        Term::factory()->create(['term' => 'blinding', 'definition' => 'Guards against bias.']);
        $bias = Term::factory()->create(['term' => 'bias', 'definition' => 'A push away from the truth.']);
        Term::factory()->draft()->create(['term' => 'biased sample']);

        $this->post('/search', ['query' => 'Bias'])->assertRedirect('/search/bias');

        $this->get('/search/bias')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Search')
                ->where('query', 'bias')
                ->has('terms', 2)
                ->where('terms.0.term', 'bias')
                ->where('terms.0.term_html', '<mark>bias</mark>')
                ->where('terms.1.definition_html', 'Guards against <mark>bias</mark>.'));

        $this->assertDatabaseHas('searches', ['search' => 'bias', 'term_id' => $bias->id]);
    }

    public function test_a_search_with_no_results_is_logged_as_no_match(): void
    {
        $this->get('/search/xyzzy')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('terms', 0));

        $this->assertDatabaseHas('searches', ['search' => 'xyzzy', 'term' => Search::NO_MATCH]);
    }

    public function test_the_top_ten_adds_up_views_across_versions(): void
    {
        Term::factory()->create(['term' => 'placebo', 'hits' => 50]);
        Term::factory()->create(['term' => 'bias', 'hits' => 30]);
        Term::factory()->revision()->create(['term' => 'bias', 'hits' => 40]);
        Term::factory()->create(['term' => 'unread', 'hits' => 0]);

        $this->get('/about/top-ten')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('TopTen')
                ->has('terms', 2)
                ->where('terms.0.term', 'bias')
                ->where('terms.0.hits', 70)
                ->where('terms.1.term', 'placebo'));
    }

    public function test_content_pages_render_and_missing_ones_are_not_found(): void
    {
        Page::factory()->create(['slug' => 'who-we-are', 'title' => 'Who we are', 'body' => '<p>Editors.</p>']);

        $this->get('/about/who-we-are')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Page')
                ->where('page.title', 'Who we are')
                ->where('page.body_html', '<p>Editors.</p>')
                ->where('form', null));

        $this->get('/about/nothing-here')->assertNotFound();
        $this->get('/get-in-touch/nothing-here')->assertNotFound();
    }

    public function test_the_feedback_form_emails_the_configured_address(): void
    {
        Mail::fake();
        config(['glossary.contact.feedback' => 'feedback@glossary.test']);

        $this->get('/get-in-touch/feedback')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('form', 'feedback')->where('sent', false));

        $this->post('/contact/send_feedback', [
            'name' => 'Pat Reader',
            'email' => 'pat@example.com',
            'feedback' => 'The definition of bias helped.',
        ])->assertRedirect('/get-in-touch/feedback');

        Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail) => $mail->hasTo('feedback@glossary.test')
            && $mail->kind === ContactMessage::FEEDBACK
            && $mail->body === 'The definition of bias helped.');

        $this->get('/get-in-touch/feedback')
            ->assertInertia(fn (Assert $page) => $page->where('sent', true));
    }

    public function test_contact_forms_validate_and_ignore_bots(): void
    {
        Mail::fake();
        config(['glossary.contact.suggestions' => 'terms@glossary.test']);

        $this->post('/contact/suggest_term', ['name' => '', 'email' => 'nope', 'terms' => ''])
            ->assertSessionHasErrors(['name', 'email', 'terms']);

        // The hidden "website" field is only ever filled in by bots.
        $this->post('/contact/suggest_translation', [
            'name' => 'Bot', 'email' => 'bot@example.com', 'suggestion' => 'Buy now', 'website' => 'http://spam.test',
        ])->assertRedirect('/get-in-touch/suggest-translation');

        Mail::assertNothingSent();

        $this->post('/contact/suggest_term', ['name' => 'Pat', 'email' => 'pat@example.com', 'terms' => 'equipoise'])
            ->assertRedirect('/get-in-touch/suggest-term');

        Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail) => $mail->hasTo('terms@glossary.test'));
    }

    public function test_i_dont_get_it_is_counted_without_changing_the_edit_date(): void
    {
        $term = Term::factory()->create(['IDGI' => 2]);
        $editedAt = $term->fresh()->updated_at;

        $this->postJson("/idgi/{$term->id}")->assertOk()->assertJson(['ok' => true]);

        $term->refresh();
        $this->assertSame(3, $term->IDGI);
        $this->assertEquals($editedAt, $term->updated_at);
    }

    public function test_old_sign_in_addresses_redirect_to_the_new_ones(): void
    {
        $this->get('/auth/login')->assertRedirect('/login');
        $this->get('/auth/register')->assertRedirect('/register');
        $this->get('/password/email')->assertRedirect('/forgot-password');
        $this->get('/password/reset/abc123')->assertRedirect('/reset-password/abc123');
        $this->get('/home')->assertRedirect('/');
        $this->get('/search')->assertRedirect('/');
    }

    public function test_comments_are_only_shown_to_signed_in_users(): void
    {
        $term = Term::factory()->create(['term' => 'bias']);
        $author = User::factory()->create(['first_name' => 'Edith', 'last_name' => 'Editor']);
        $term->comments()->create(['comment' => 'Reads well.', 'author_id' => $author->id]);

        $this->get('/term/bias')->assertInertia(fn (Assert $page) => $page->where('comments', null));

        $this->actingAs(User::factory()->create())->get('/term/bias')
            ->assertInertia(fn (Assert $page) => $page
                ->has('comments', 1)
                ->where('comments.0.comment', 'Reads well.')
                ->where('comments.0.author_name', 'Edith Editor')
                ->where('comments.0.can_delete', false));
    }
}
