<?php

namespace Tests\Unit;

use App\Support\TermLinker;
use PHPUnit\Framework\TestCase;

class TermLinkerTest extends TestCase
{
    public function test_a_bracketed_term_becomes_a_link_to_that_term(): void
    {
        $this->assertSame(
            'Limits <a href="/term/bias">bias</a> in a study.',
            TermLinker::link('Limits [bias] in a study.'),
        );
    }

    public function test_piped_syntax_links_other_words_to_a_term(): void
    {
        $this->assertSame(
            'All the <a href="/term/randomised+trial">trials</a> found.',
            TermLinker::link('All the [trials|randomised trial] found.'),
        );
    }

    public function test_plain_drops_the_syntax_and_keeps_the_visible_words(): void
    {
        $this->assertSame(
            'All the trials on bias.',
            TermLinker::plain('All the [trials|randomised trial] on [bias].'),
        );
    }

    public function test_strong_emphasises_without_linking(): void
    {
        $this->assertSame('A <strong>study</strong>.', TermLinker::strong('A [study].'));
    }

    public function test_terms_with_accented_letters_and_hyphens_are_linked(): void
    {
        $this->assertSame(
            '<a href="/term/meta-analysis">meta-analysis</a> og <a href="/term/skjevhet+%C3%A5">skjevhet å</a>',
            TermLinker::link('[meta-analysis] og [skjevhet å]'),
        );
    }

    public function test_text_without_references_is_returned_untouched(): void
    {
        $this->assertSame('<p>Nothing [] to link.</p>', TermLinker::link('<p>Nothing [] to link.</p>'));
        $this->assertSame('', TermLinker::link(null));
    }

    public function test_slugs_use_plus_for_spaces_and_round_trip(): void
    {
        $this->assertSame('absolute+risk+reduction', TermLinker::slug('absolute risk reduction'));
        $this->assertSame('absolute risk reduction', TermLinker::unslug('Absolute+Risk+reduction'));
    }

    public function test_double_exclamation_marks_become_the_warning_icon(): void
    {
        $this->assertStringContainsString('class="warn-mark"', TermLinker::warnings('!!sugar pill'));
        $this->assertStringNotContainsString('!!', TermLinker::warnings('!!sugar pill'));
    }
}
