<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Search;
use App\Models\Term;
use App\Models\User;
use App\Support\TermLinker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Editorial comments. Signed-in users discuss a definition underneath it;
 * comments belong to one version of a term and the thread view gathers
 * them across every version.
 */
class CommentController extends Controller
{
    /**
     * The whole discussion of a term, version by version.
     */
    public function thread(Request $request, string $term): Response|HttpResponse
    {
        $query = TermLinker::unslug($term);

        /** @var User $user */
        $user = $request->user();

        $found = Term::query()->live()
            ->named($query)
            ->first()
            ?? Term::query()->live()->where('synonyms', 'LIKE', '%'.$query.'%')->first();

        if (! $found) {
            Search::record($query, null, $request->ip());

            return Inertia::render('Comments', [
                'query' => $query,
                'term' => null,
                'versions' => [],
                'letter' => null,
            ])->toResponse($request)->setStatusCode(404);
        }

        $versions = $found->versions()
            ->with(['updater', 'comments' => fn ($q) => $q->oldest('id'), 'comments.author'])
            ->get()
            ->filter(fn (Term $version): bool => $version->comments->isNotEmpty())
            ->map(fn (Term $version): array => [
                'id' => $version->id,
                'version' => $version->version,
                'definition_html' => trim(TermLinker::link($version->definition)),
                'updated_by' => $version->version > 1 ? $version->updater?->name : null,
                'date' => ($version->version === 1 ? $version->created_at : $version->updated_at)
                    ?->translatedFormat('l d F Y'),
                'comments' => $version->comments
                    ->map(fn (Comment $comment): array => self::present($comment, $user, absoluteDate: true))
                    ->all(),
            ])
            ->values();

        return Inertia::render('Comments', [
            'query' => $query,
            'letter' => null,
            'term' => [
                'id' => $found->id,
                'term' => $found->term,
                'path' => $found->path(),
                'definition_html' => trim(TermLinker::link($found->definition)),
            ],
            'versions' => $versions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'comment' => ['required', 'string', 'max:10000'],
        ]);

        Comment::query()->create([
            'term_id' => $data['term_id'],
            'comment' => $data['comment'],
            'author_id' => $request->user()->id,
        ]);

        $request->session()->flash('glossary.skip_hit', true);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment posted.')]);

        return back();
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->is_admin || $comment->author_id === $user->id, 403);

        $comment->delete();

        $request->session()->flash('glossary.skip_hit', true);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment deleted.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(Comment $comment, User $viewer, bool $absoluteDate = false): array
    {
        return [
            'id' => $comment->id,
            'comment' => $comment->comment,
            'author_id' => $comment->author_id,
            'author_name' => $comment->author->name,
            'date' => $absoluteDate
                ? $comment->created_at?->translatedFormat('l d F Y')
                : $comment->created_at?->diffForHumans(),
            'can_delete' => $viewer->is_admin || $comment->author_id === $viewer->id,
        ];
    }
}
