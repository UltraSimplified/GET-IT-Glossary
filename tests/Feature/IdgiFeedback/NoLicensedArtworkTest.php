<?php

namespace Tests\Feature\IdgiFeedback;

use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The monkey pictures are licensed to one organisation, so the code ships
 * the built-in bubbles only. Sites licensed for the monkey upload it.
 */
class NoLicensedArtworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_monkey_pictures_are_not_part_of_the_code(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('assets/img/IDGI'));
    }

    public function test_a_fresh_install_shows_the_built_in_bubbles(): void
    {
        Term::factory()->create(['term' => 'placebo']);

        $this->get('/term/placebo')
            ->assertOk()
            ->assertDontSee('assets/img/IDGI')
            ->assertInertia(fn (Assert $page) => $page->where('glossary.idgi', null));
    }
}
