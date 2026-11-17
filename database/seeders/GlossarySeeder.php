<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Page;
use App\Models\Term;
use App\Models\TermEditor;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample content for local development: enough terms, pages, versions and
 * comments to exercise every screen. Replace with a real import for
 * production.
 */
class GlossarySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $editor = User::query()->where('email', 'editor@example.com')->firstOrFail();
        $contributor = User::query()->where('email', 'contributor@example.com')->firstOrFail();

        foreach ($this->terms() as $data) {
            $term = Term::query()->create([
                'term_ordered' => $data['term'],
                'term_status' => Term::STATUS_LIVE,
                'enabled' => true,
                'version' => 1,
                'tags' => '',
                'updated_by' => $admin->id,
                ...$data,
            ]);

            $term->forceFill(['master_id' => $term->id])->saveQuietly();
        }

        $this->seedHistory($admin, $editor, $contributor);
        $this->seedPages($admin);

        // The editor looks after three terms.
        foreach (['bias', 'blinding', 'placebo'] as $name) {
            $term = Term::query()->where('term', $name)->live()->firstOrFail();

            TermEditor::query()->create(['term_id' => $term->master_id, 'user_id' => $editor->id]);
        }
    }

    /**
     * Give "bias" three versions with a discussion, to show comparing,
     * rolling back and the comment thread.
     */
    private function seedHistory(User $admin, User $editor, User $contributor): void
    {
        $current = Term::query()->where('term', 'bias')->firstOrFail();

        $first = $current->replicate()->fill([
            'version' => 1,
            'term_status' => Term::STATUS_REVISION,
            'enabled' => false,
            'definition' => 'A systematic error in a [study].',
            'explanation' => 'Bias means the results of a study differ from the truth because of how the study was done.',
            'hits' => 212,
            'IDGI' => 96,
        ]);
        $first->created_at = now()->subMonths(14);
        $first->updated_at = now()->subMonths(14);
        $first->save();

        $second = $current->replicate()->fill([
            'version' => 2,
            'term_status' => Term::STATUS_REVISION,
            'enabled' => false,
            'definition' => 'Something in the way a [study] is done that makes its results wrong in a particular direction.',
            'updated_by' => $editor->id,
            'hits' => 148,
            'IDGI' => 31,
        ]);
        $second->created_at = now()->subMonths(14);
        $second->updated_at = now()->subMonths(5);
        $second->save();

        // History rows share the master_id of the first version.
        Term::query()->whereIn('id', [$first->id, $second->id, $current->id])->update(['master_id' => $first->id]);

        $current->forceFill([
            'master_id' => $first->id,
            'version' => 3,
            'updated_by' => $editor->id,
            'updated_at' => now()->subDays(9),
        ])->saveQuietly();

        $comments = [
            [$first, $contributor, '“Systematic error” is exactly the kind of phrase we are trying to explain. Could the definition avoid it?', now()->subMonths(6)],
            [$first, $editor, 'Agreed. I will try a version that describes what bias does instead of naming it.', now()->subMonths(6)->addDay()],
            [$second, $admin, 'Much clearer. “Wrong in a particular direction” is doing a lot of work though. Is “pushes the results away from the truth” easier to picture?', now()->subWeeks(3)],
            [$current, $editor, 'Reworded along those lines and added the example about people dropping out.', now()->subDays(9)],
            [$current, $contributor, 'This reads well now. Tested it on two colleagues outside research and both could explain it back.', now()->subDays(2)],
        ];

        foreach ($comments as [$version, $author, $text, $when]) {
            $comment = new Comment(['term_id' => $version->id, 'author_id' => $author->id, 'comment' => $text]);
            $comment->created_at = $when;
            $comment->updated_at = $when;
            $comment->save();
        }
    }

    private function seedPages(User $admin): void
    {
        $pages = [
            ['home', 'Home', null, null,
                '<p>Plain language definitions of health research terms.</p>'],
            ['who-we-are', 'Who we are', Page::MENU_ABOUT, 1,
                '<p>The glossary is written and maintained by a group of health researchers, clinicians, patients and plain language editors.</p><p>Every definition is drafted by an editor, discussed by the group and tested with people who do not work in research before it is published.</p>'],
            ['purpose-of-get-it', 'About GET-IT', Page::MENU_ABOUT, 2,
                '<p>GET-IT stands for the Glossary of Evaluation Terms for Informed Treatment choices. It exists so that anyone can understand the words used when treatments are tested and compared.</p><h2>What makes a good definition</h2><ul><li>It is short enough to read in one breath.</li><li>It uses everyday words, and links to the glossary when it cannot.</li><li>It says what the term means for a person making a health choice.</li></ul>'],
            ['development', 'Development', Page::MENU_ABOUT, 3,
                '<p>The glossary is under continuous development. Terms are revised when readers tell us a definition did not help, which they do by pressing the monkey at the foot of each term.</p>'],
            ['what-is-the-api', 'What is the API?', Page::MENU_API, 1,
                '<p>The API lets other websites and apps show glossary definitions. It is free to use and needs no key.</p><p>Fetch every published term from <code>/v1/terms</code>, or a single term from <code>/v1/terms/placebo</code>. Both return JSON.</p>'],
            ['embed-get-it', 'Embed GET-IT on your own web site', Page::MENU_API, 2,
                '<p>Add the glossary to any page with a single iframe pointing at <code>/widget</code>. Visitors pick a term and read its definition without leaving your site.</p>'],
            ['download-wordpress-plugin', 'Download the WordPress plugin', Page::MENU_API, 3,
                '<p>The WordPress plugin underlines glossary terms in your posts and shows the definition when a reader taps one.</p><p><a href="/downloads/wp-getit.zip">Download the plugin (zip)</a></p>'],
            ['feedback', 'Send us your feedback', Page::MENU_CONTACT, 1,
                '<p>Tell us what works, what does not, and what you would like to see next.</p>'],
            ['suggest-term', 'Suggest a term', Page::MENU_CONTACT, 2,
                '<p>Is there a health research term you have come across that the glossary does not explain? List one or more below.</p>'],
            ['suggest-translation', 'Suggest a translation', Page::MENU_CONTACT, 3,
                '<p>The glossary is being translated into other languages. If you can improve a translation, or would like to help with a new language, let us know.</p>'],
        ];

        foreach ($pages as [$slug, $title, $menu, $position, $body]) {
            Page::query()->create([
                'slug' => $slug,
                'title' => $title,
                'menu' => $menu,
                'position' => $position,
                'body' => $body,
                'updated_by' => $admin->id,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function terms(): array
    {
        return [
            [
                'term' => 'absolute risk reduction',
                'term_ordered' => 'risk reduction, absolute',
                'synonyms' => 'risk difference; ARR',
                'see_also' => 'relative risk reduction; number needed to treat',
                'definition' => 'How much a [treatment] lowers the chance of something happening, shown as the plain difference between two groups.',
                'explanation' => "If 10 in every 100 people have a heart attack without a treatment and 6 in every 100 have one with it, the absolute risk reduction is 4 in 100.\n\nIt tells you how many people are actually helped, which the [relative risk reduction] can hide.",
                'example' => '<p>“The drug lowered the risk of stroke from 3% to 2%: an absolute risk reduction of 1 percentage point.”</p>',
                'hits' => 412, 'IDGI' => 61,
            ],
            [
                'term' => 'bias',
                'synonyms' => 'systematic error',
                'see_also' => 'blinding; fair comparison; randomised trial',
                'definition' => 'Anything in the way a [study] is planned, run or reported that pushes its results away from the truth.',
                'explanation' => "Bias is not the same as chance. Chance can make a result too high or too low; bias pushes every time in the same direction.\n\nFor example, if the people who feel worse on a [treatment] drop out of a study and are not counted, the treatment will look better than it is. A [fair comparison] is designed to keep bias out.",
                'example' => '<p>“Because the doctors knew who was getting the new drug, their judgement of who had improved may have been biased.”</p>',
                'tags' => 'testing treatments',
                'hits' => 536, 'IDGI' => 48,
            ],
            [
                'term' => 'blinding',
                'synonyms' => 'masking',
                'see_also' => 'bias; placebo',
                'definition' => 'Keeping people in a [study] from knowing which [treatment] each [participant] is getting.',
                'explanation' => "When people know which treatment they are getting, what they expect can change how they feel and what they report. The same goes for the doctors and researchers measuring the results.\n\nBlinding stops those expectations from causing [bias]. A [placebo] is often what makes blinding possible.",
                'tags' => 'testing treatments',
                'hits' => 298, 'IDGI' => 22,
            ],
            [
                'term' => 'certainty of the evidence',
                'term_ordered' => 'evidence, certainty of the',
                'synonyms' => 'quality of the evidence; !!level of evidence',
                'see_also' => 'evidence; systematic review',
                'definition' => 'How sure we can be that what the [evidence] shows is close to the truth.',
                'explanation' => 'Certainty is usually described as high, moderate, low or very low. It goes down when [study|studies] had a risk of [bias], disagreed with each other, were small, or measured something only loosely related to what matters to people.',
                'warning' => '“Level of evidence” suggests a fixed ranking by type of study. Certainty depends on how the studies were done, not only on what kind they were.',
                'tags' => 'guidelines,iSoF',
                'hits' => 121, 'IDGI' => 44,
            ],
            [
                'term' => 'confidence interval',
                'term_ordered' => 'interval, confidence',
                'synonyms' => 'CI; !!margin of error',
                'see_also' => 'estimate; statistically significant',
                'definition' => 'A range around an [estimate] that shows how precise it is.',
                'explanation' => "No [study] can measure an effect exactly. The confidence interval shows the range the true effect is likely to lie in.\n\nA narrow interval means the estimate is precise. A wide one means the study cannot tell us much, usually because too few people took part.",
                'example' => '<p>“The treatment shortened a cold by 1 day (95% confidence interval: half a day to a day and a half).”</p>',
                'hits' => 120, 'IDGI' => 48,
            ],
            [
                'term' => 'control group',
                'term_ordered' => 'group, control',
                'synonyms' => 'comparison group; controls',
                'see_also' => 'fair comparison; placebo',
                'definition' => 'The people in a [study] who do not get the [treatment] being tested, so there is something to compare it with.',
                'explanation' => 'Without a control group there is no way to know whether people got better because of the treatment or would have got better anyway. The control group may get a [placebo], the usual treatment, or nothing.',
                'hits' => 264, 'IDGI' => 12,
            ],
            [
                'term' => 'diagnostic test',
                'term_ordered' => 'test, diagnostic',
                'definition' => 'A test done to find out whether a person has a particular condition.',
                'explanation' => 'No test is right every time. A good test rarely misses the condition in people who have it, and rarely finds it in people who do not.',
                'tags' => 'diagnosis',
                'hits' => 73, 'IDGI' => 4,
            ],
            [
                'term' => 'estimate',
                'see_also' => 'confidence interval',
                'definition' => 'The best answer a [study] can give for the size of an effect.',
                'explanation' => 'It is called an estimate because it comes from the people who happened to take part. A different group of people would give a slightly different answer, which is why an estimate should come with a [confidence interval].',
                'hits' => 88, 'IDGI' => 9,
            ],
            [
                'term' => 'evidence',
                'see_also' => 'certainty of the evidence; systematic review',
                'definition' => 'Facts gathered in [study|studies] that help answer a question about health.',
                'explanation' => 'Not all evidence is equally trustworthy. How much weight to give it depends on how the studies were done.',
                'hits' => 301, 'IDGI' => 15,
            ],
            [
                'term' => 'fair comparison',
                'term_ordered' => 'comparison, fair',
                'synonyms' => 'fair test',
                'see_also' => 'bias; randomised trial',
                'definition' => 'A comparison of [treatment|treatments] set up so that the treatment is the only important difference between the groups.',
                'explanation' => 'If the groups differ in other ways (age, how ill they are, how closely they are watched) nobody can tell whether a difference in results came from the treatment. A [randomised trial] is the surest way to make a comparison fair.',
                'tags' => 'testing treatments',
                'hits' => 190, 'IDGI' => 7,
            ],
            [
                'term' => 'harm',
                'see_also' => 'side effect; outcome',
                'definition' => 'Any bad [outcome] caused by a [treatment].',
                'explanation' => 'Every treatment that can do good can also do harm. A fair account of a treatment reports both.',
                'hits' => 67, 'IDGI' => 2,
            ],
            [
                'term' => 'health technology assessment',
                'term_ordered' => 'assessment, health technology',
                'synonyms' => 'HTA',
                'definition' => 'A careful look at what a [treatment] or test does, what it costs and what it means for patients and health services.',
                'explanation' => 'Health services use these assessments to decide which treatments to pay for.',
                'tags' => 'HTA,economics',
                'hits' => 41, 'IDGI' => 11,
            ],
            [
                'term' => 'meta-analysis',
                'see_also' => 'systematic review; estimate',
                'definition' => 'A way of combining the results of several similar [study|studies] into one overall [estimate].',
                'explanation' => 'Putting studies together gives a more precise answer than any one of them alone. It is usually done as part of a [systematic review].',
                'hits' => 233, 'IDGI' => 58,
            ],
            [
                'term' => 'number needed to treat',
                'synonyms' => 'NNT',
                'see_also' => 'absolute risk reduction',
                'definition' => 'How many people need to have a [treatment] for one of them to benefit.',
                'explanation' => "If a treatment prevents a heart attack in 4 of every 100 people who take it, 25 people need to take it to prevent one heart attack. The number needed to treat is 25.\n\nThe smaller the number, the more people the treatment helps.",
                'hits' => 176, 'IDGI' => 40,
            ],
            [
                'term' => 'observational study',
                'term_ordered' => 'study, observational',
                'see_also' => 'randomised trial; bias',
                'definition' => 'A [study] in which researchers watch what happens to people without deciding who gets which [treatment].',
                'explanation' => 'Because people (or their doctors) chose their own treatment, the groups may differ in ways that matter. That makes it harder to be sure the treatment caused any difference in results.',
                'hits' => 95, 'IDGI' => 13,
            ],
            [
                'term' => 'outcome',
                'synonyms' => 'endpoint; outcome measure',
                'definition' => 'Something that can happen to people in a [study] that researchers measure, such as pain, a heart attack or how well they can get about.',
                'explanation' => 'The outcomes that matter most are the ones that matter to patients. A blood test result is only useful as an outcome if it reliably tells us about something people can feel.',
                'hits' => 254, 'IDGI' => 10,
            ],
            [
                'term' => 'participant',
                'synonyms' => '!!subject; volunteer',
                'definition' => 'A person who takes part in a [study].',
                'explanation' => 'Participants agree to take part after being told what the study involves.',
                'warning' => '“Subject” is still common in research papers. Many people find it impersonal, so the glossary uses “participant”.',
                'hits' => 143, 'IDGI' => 3,
            ],
            [
                'term' => 'placebo',
                'synonyms' => 'dummy treatment; !!sugar pill',
                'see_also' => 'blinding; control group',
                'definition' => 'A dummy [treatment] made to look and feel like the real one, but with nothing active in it.',
                'explanation' => "A placebo lets researchers compare like with like. If everyone takes an identical-looking pill, nobody knows who is getting the real [treatment], which makes [blinding] possible.\n\nPeople often feel better after any treatment, real or not. Comparing against a placebo shows how much of the improvement is down to the treatment itself.",
                'example' => '<p>“Half the participants took the new tablet and half took a placebo tablet that looked the same.”</p>',
                'tags' => 'testing treatments',
                'hits' => 300, 'IDGI' => 12,
            ],
            [
                'term' => 'quality-adjusted life year',
                'term_ordered' => 'life year, quality-adjusted',
                'synonyms' => 'QALY',
                'definition' => 'A measure that counts both how long a [treatment] helps people live and how good that life is.',
                'explanation' => 'One year in full health counts as one. A year in poorer health counts as less than one.',
                'tags' => 'economics,HTA',
                'hits' => 36, 'IDGI' => 19,
            ],
            [
                'term' => 'randomised trial',
                'term_ordered' => 'trial, randomised',
                'synonyms' => 'randomised controlled trial; RCT',
                'common_variants' => 'randomized trial; randomized controlled trial; randomised controlled trial',
                'see_also' => 'fair comparison; control group; blinding',
                'definition' => 'A [study] in which chance alone decides which [treatment] each [participant] gets.',
                'explanation' => "Leaving the choice to chance, like tossing a coin, means the groups being compared end up alike in every way that matters. Any difference in their results can then be put down to the treatment.\n\nThis makes a randomised trial the most reliable kind of [fair comparison].",
                'tags' => 'testing treatments',
                'hits' => 489, 'IDGI' => 27,
            ],
            [
                'term' => 'relative risk reduction',
                'term_ordered' => 'risk reduction, relative',
                'synonyms' => 'RRR',
                'see_also' => 'absolute risk reduction',
                'definition' => 'How much a [treatment] lowers the chance of something happening, shown as a share of the chance without it.',
                'explanation' => "If a treatment cuts the chance of a stroke from 2 in 100 to 1 in 100, the relative risk reduction is a half, or 50%.\n\nThat sounds large, but only 1 person in 100 is helped. Always ask for the [absolute risk reduction] as well.",
                'hits' => 207, 'IDGI' => 66,
            ],
            [
                'term' => 'side effect',
                'term_ordered' => 'effect, side',
                'synonyms' => 'adverse effect; adverse reaction',
                'see_also' => 'harm',
                'definition' => 'An unwanted effect of a [treatment].',
                'explanation' => 'Side effects range from mild and short-lived to serious. Not everything that happens after a treatment is caused by it; a [control group] helps tell which is which.',
                'hits' => 322, 'IDGI' => 5,
            ],
            [
                'term' => 'statistically significant',
                'term_ordered' => 'significant, statistically',
                'synonyms' => '!!significant',
                'see_also' => 'confidence interval',
                'do_not_use' => true,
                'definition' => 'Describes a result that would be unlikely to turn up if the [treatment] really made no difference.',
                'explanation' => "It does not mean the result is important, or large, or that the treatment works for you. A tiny difference can be statistically significant in a big [study].\n\nA [confidence interval] says more: it shows how big the effect might be.",
                'warning' => 'Readers often take “significant” to mean “important”. Say how big the effect is instead.',
                'hits' => 80, 'IDGI' => 56,
            ],
            [
                'term' => 'study',
                'synonyms' => 'research study',
                'see_also' => 'randomised trial; observational study',
                'definition' => 'An investigation set up to answer a question.',
                'explanation' => 'In health research the question is often whether one [treatment] works better than another.',
                'hits' => 510, 'IDGI' => 6,
            ],
            [
                'term' => 'systematic review',
                'term_ordered' => 'review, systematic',
                'see_also' => 'meta-analysis; evidence',
                'definition' => 'A summary of all the [study|studies] on a question, found and judged in a way that limits [bias].',
                'explanation' => 'A single study can mislead. A systematic review sets out beforehand how studies will be found and assessed, then brings them together so the whole picture can be seen. Its results are often combined in a [meta-analysis].',
                'tags' => 'guidelines',
                'hits' => 377, 'IDGI' => 34,
            ],
            [
                'term' => 'treatment',
                'synonyms' => 'intervention; therapy',
                'definition' => 'Anything done to try to improve someone’s health.',
                'explanation' => 'A treatment can be a medicine, an operation, a change in diet, exercise, advice or a talking therapy.',
                'hits' => 468, 'IDGI' => 4,
            ],
            [
                // A draft, to show the management list handling unpublished terms.
                'term' => 'surrogate outcome',
                'term_ordered' => 'outcome, surrogate',
                'term_status' => Term::STATUS_DRAFT,
                'enabled' => false,
                'definition' => 'Something measured in a [study] because it is quicker to see than the [outcome] that really matters.',
                'explanation' => 'Blood pressure is a surrogate for strokes and heart attacks.',
                'hits' => 0, 'IDGI' => 0,
            ],
        ];
    }
}
