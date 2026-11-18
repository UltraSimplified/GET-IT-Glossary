<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Support\TermLinker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The public v1 API (used by the WordPress plugin and embeds on other
 * sites) plus the small JSON lookups the site's own search box uses.
 *
 * Response shapes are part of the public contract: do not change them.
 */
class TermApiController extends Controller
{
    /**
     * GET /v1/terms — every published term with its definition.
     */
    public function index(): JsonResponse
    {
        $terms = Term::query()->live()->orderBy('term')->get(['term', 'definition']);

        return $this->open(response()->json($terms));
    }

    /**
     * GET /v1/terms/{term} — a list holding the one matching term, or empty.
     */
    public function show(string $term): JsonResponse
    {
        $query = TermLinker::unslug($term);

        $terms = Term::query()->live()
            ->named($query)
            ->limit(1)
            ->get(['term', 'definition']);

        return $this->open(response()->json($terms));
    }

    /**
     * GET /v1/hit/{query} — count a view made through an embed and return
     * the new total (0 when the term is unknown).
     */
    public function hit(string $query): Response
    {
        $query = TermLinker::unslug($query);

        $term = Term::query()->live()
            ->named($query)
            ->first();

        return $this->open(response((string) ($term ? $term->recordHit() : 0)));
    }

    /**
     * GET /v1/terms-list — a plain HTML table of every published term.
     */
    public function list(Request $request): View
    {
        $terms = Term::query()->live()
            ->where('term', 'LIKE', '%'.$request->string('query').'%')
            ->orderBy('term')
            ->get(['id', 'term']);

        return view('terms-list', ['terms' => $terms]);
    }

    /**
     * GET /ajax/terms?query= — suggestions for the search box.
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('query'));

        if ($query === '') {
            return response()->json([]);
        }

        $terms = Term::query()->live()
            ->where('term', 'LIKE', '%'.$query.'%')
            ->orderBy('term')
            ->limit(12)
            ->get(['id', 'term'])
            ->map(fn (Term $term): array => [
                'id' => $term->id,
                'name' => $term->term,
                'path' => $term->path(),
            ]);

        return response()->json($terms);
    }

    /**
     * GET /ajax/autocomplete — every published term name, for the
     * "[term]" cross-reference helper in the term editor.
     */
    public function names(): JsonResponse
    {
        return response()->json(
            Term::query()->live()->orderBy('term')->pluck('term'),
        );
    }

    /**
     * The v1 endpoints are called from other people's websites.
     *
     * @template T of \Symfony\Component\HttpFoundation\Response
     *
     * @param  T  $response
     * @return T
     */
    private function open($response)
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
