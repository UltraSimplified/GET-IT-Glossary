<?php

namespace Tests\Feature\IdgiFeedback;

use App\Models\IdgiReply;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RepliesStayPrivateTest extends TestCase
{
    use RefreshDatabase;

    private const string SECRET = 'A reply only editors should read';

    public function test_a_term_page_sends_no_replies_to_visitors_or_contributors(): void
    {
        $term = Term::factory()->create(['term' => 'placebo']);
        IdgiReply::factory()->for($term)->create(['message' => self::SECRET]);

        $this->get('/term/placebo')
            ->assertOk()
            ->assertDontSee(self::SECRET)
            ->assertInertia(fn (Assert $page) => $page->missing('replies')->missing('term.replies'));

        $this->actingAs(User::factory()->create())
            ->get('/term/placebo')
            ->assertOk()
            ->assertDontSee(self::SECRET)
            ->assertInertia(fn (Assert $page) => $page->missing('replies')->missing('term.replies'));
    }

    public function test_the_api_and_widget_respond_exactly_as_before(): void
    {
        $term = Term::factory()->create(['term' => 'placebo', 'definition' => 'A dummy [treatment].']);

        $before = [
            $this->get('/v1/terms')->getContent(),
            $this->get('/v1/terms/placebo')->getContent(),
            $this->get('/widget?term=placebo')->getContent(),
        ];

        IdgiReply::factory()->for($term)->create(['message' => self::SECRET]);

        $this->assertSame($before, [
            $this->get('/v1/terms')->getContent(),
            $this->get('/v1/terms/placebo')->getContent(),
            $this->get('/widget?term=placebo')->getContent(),
        ]);
    }
}
