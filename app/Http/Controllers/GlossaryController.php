<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Search;
use App\Models\Term;
use App\Models\User;
use App\Services\TermSearch;
use App\Support\TermLinker;
use App\Support\TermPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The public face of the glossary: home, A–Z listings, term pages,
 * search and the top ten.
 */
class GlossaryController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Home');
    }

    /**
     * Every published term filed under a letter, either by its own name or
     * by its alphabetised form ("risk, absolute" under R).
     */
    public function listing(string $letter): Response
    {
        $letter = mb_substr(str_replace(['%', '_'], '', trim($letter)), 0, 40);
        $needle = mb_strtolower($letter);

        $terms = $letter === '' ? collect() : Term::query()->live()
            ->where(fn (Builder $query) => $query
                ->where('term', 'LIKE', $letter.'%')
                ->orWhere('term_ordered', 'LIKE', $letter.'%'))
            ->get()
            ->map(function (Term $term) use ($needle): array {
                $direct = str_starts_with(mb_strtolower($term->term), $needle);

                return [
                    ...TermPresenter::summary($term),
                    // When set, the entry is a cross-reference: "risk, absolute — see absolute risk".
                    'listed_as' => $direct ? null : $term->term_ordered,
                    'sort' => mb_strtolower($direct ? $term->term : $term->term_ordered),
                ];
            })
            ->sortBy('sort')
            ->values()
            ->map(fn (array $term): array => collect($term)->except('sort')->all());

        return Inertia::render('Listing', [
            'letter' => mb_strtoupper($letter),
            'terms' => $terms,
        ]);
    }

    /**
     * A single term. Counts the view and logs the lookup, as the original
     * site did, so the usage figures keep their meaning.
     */
    public function term(Request $request, ?string $term = null): Response|HttpResponse
    {
        $query = TermLinker::unslug((string) $term);

        if ($query === '') {
            return Inertia::render('Term', [
                'query' => '',
                'term' => null,
                'letter' => null,
            ]);
        }

        $found = Term::findPublished($query);

        // A redirect back here after posting or deleting a comment is not a new view.
        $countView = ! $request->session()->pull('glossary.skip_hit', false);

        if (! $found) {
            if ($countView) {
                Search::record($query, null, $request->ip());
            }

            return Inertia::render('Term', [
                'query' => $query,
                'term' => null,
                'letter' => null,
            ])->toResponse($request)->setStatusCode(404);
        }

        if ($countView) {
            $found->recordHit();
            Search::record($query, $found, $request->ip());
        }

        /** @var User|null $user */
        $user = $request->user();

        return Inertia::render('Term', [
            'query' => $query,
            'term' => TermPresenter::full($found),
            'letter' => mb_strtoupper(mb_substr($found->term, 0, 1)),
            'adjacent' => [
                'previous' => $this->neighbour($found, '<', 'desc'),
                'next' => $this->neighbour($found, '>', 'asc'),
            ],
            'canEdit' => $user?->canEdit($found) ?? false,
            'comments' => $user ? $this->comments($found, $user) : null,
        ]);
    }

    /**
     * The search box posts here; the results live at a shareable URL.
     */
    public function searchRedirect(Request $request): RedirectResponse
    {
        $query = trim(mb_strtolower((string) $request->input('query')));

        return $query === ''
            ? redirect('/')
            : redirect('/search/'.TermLinker::slug($query));
    }

    public function search(Request $request, TermSearch $search, string $query): Response|RedirectResponse
    {
        $query = TermLinker::unslug($query);

        if ($query === '') {
            return redirect('/');
        }

        $terms = $search->search($query);

        Search::record(
            $query,
            $terms->first(),
            $request->ip(),
            $terms->isEmpty() ? Search::NO_MATCH : null,
        );

        return Inertia::render('Search', [
            'query' => $query,
            'letter' => null,
            'terms' => $terms->map(function (Term $term) use ($search, $query): array {
                $summary = TermPresenter::summary($term);

                return [
                    ...$summary,
                    'term_html' => $search->highlight(e($term->term), $query),
                    'synonyms_html' => $search->highlight($summary['synonyms_html'], $query),
                    'definition_html' => $search->highlight($summary['definition_html'], $query),
                ];
            })->values(),
        ]);
    }

    /**
     * The ten most viewed terms, adding together the views of every
     * version of each term.
     */
    public function topTen(): Response
    {
        $views = DB::table('terms')
            ->selectRaw('term, SUM(hits) AS total')
            ->groupBy('term')
            ->pluck('total', 'term');

        $terms = Term::query()->live()->get()
            ->map(fn (Term $term): array => [
                ...TermPresenter::summary($term),
                'hits' => (int) ($views[$term->term] ?? $term->hits),
            ])
            ->filter(fn (array $term): bool => $term['hits'] > 0)
            ->sortByDesc('hits')
            ->take(10)
            ->values();

        return Inertia::render('TopTen', [
            'letter' => null,
            'terms' => $terms,
        ]);
    }

    /**
     * The published term immediately before or after this one, A to Z.
     *
     * @param  '<'|'>'  $operator
     * @param  'asc'|'desc'  $direction
     * @return array{term: string, path: string}|null
     */
    private function neighbour(Term $term, string $operator, string $direction): ?array
    {
        $neighbour = Term::query()->live()
            ->where('term', $operator, $term->term)
            ->orderBy('term', $direction)
            ->first(['id', 'term']);

        return $neighbour ? ['term' => $neighbour->term, 'path' => $neighbour->path()] : null;
    }

    /**
     * Editorial comments on this version of the term.
     *
     * @return list<array<string, mixed>>
     */
    private function comments(Term $term, User $user): array
    {
        return array_values($term->comments()->with('author')->oldest('id')->get()
            ->map(fn (Comment $comment): array => CommentController::present($comment, $user))
            ->all());
    }
}
