<?php

namespace Tests\Feature\Glossary;

use App\Models\Page;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The v1 API and widget are used by other websites, so these tests pin
 * down their exact response shapes.
 */
class ApiAndEmbedTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_terms_lists_published_terms_for_any_origin(): void
    {
        Term::factory()->create(['term' => 'placebo', 'definition' => 'A dummy [treatment].']);
        Term::factory()->create(['term' => 'bias', 'definition' => 'A push away from the truth.']);
        Term::factory()->draft()->create(['term' => 'hidden']);

        $this->get('/v1/terms')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertExactJson([
                ['term' => 'bias', 'definition' => 'A push away from the truth.'],
                ['term' => 'placebo', 'definition' => 'A dummy [treatment].'],
            ]);
    }

    public function test_v1_term_returns_a_list_with_the_single_match(): void
    {
        Term::factory()->create(['term' => 'fair comparison', 'definition' => 'Like with like.']);

        $this->get('/v1/terms/fair+comparison')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertExactJson([['term' => 'fair comparison', 'definition' => 'Like with like.']]);

        $this->get('/v1/terms/unknown')->assertOk()->assertExactJson([]);
    }

    public function test_v1_hit_counts_a_view_and_returns_the_total(): void
    {
        $term = Term::factory()->create(['term' => 'placebo', 'hits' => 9]);

        $this->get('/v1/hit/placebo')->assertOk()->assertContent('10');
        $this->get('/v1/hit/unknown')->assertOk()->assertContent('0');

        $this->assertSame(10, $term->fresh()->hits);
    }

    public function test_the_terms_list_and_widget_render_published_terms(): void
    {
        Term::factory()->create(['term' => 'placebo', 'definition' => 'A dummy [treatment] with "nothing" in it.']);
        Term::factory()->draft()->create(['term' => 'hidden']);

        $this->get('/v1/terms-list')->assertOk()->assertSee('placebo')->assertDontSee('hidden');

        $this->get('/widget')
            ->assertOk()
            ->assertSee('<option value="A dummy &lt;strong&gt;treatment&lt;/strong&gt; with &quot;nothing&quot; in it.">placebo</option>', false)
            ->assertDontSee('hidden');
    }

    public function test_the_sitemap_lists_the_home_page_pages_and_terms(): void
    {
        Term::factory()->create(['term' => 'Fair comparison']);
        Page::factory()->create(['slug' => 'who-we-are', 'menu' => Page::MENU_ABOUT]);
        Page::factory()->create(['slug' => 'home']);

        $response = $this->get('/sitemap')->assertOk();

        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));
        $response->assertSee('<loc>'.url('/').'</loc>', false)
            ->assertSee('<loc>'.url('/about/who-we-are').'</loc>', false)
            ->assertSee('<loc>'.url('/term/fair+comparison').'</loc>', false);
    }

    public function test_search_suggestions_and_editor_autocomplete(): void
    {
        Term::factory()->create(['term' => 'placebo']);
        Term::factory()->create(['term' => 'blinding']);

        $this->getJson('/ajax/terms?query=plac')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'placebo')
            ->assertJsonPath('0.path', '/term/placebo');

        $this->getJson('/ajax/terms?query=')->assertOk()->assertExactJson([]);

        $this->getJson('/ajax/autocomplete')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/ajax/autocomplete')
            ->assertOk()->assertExactJson(['blinding', 'placebo']);
    }
}
