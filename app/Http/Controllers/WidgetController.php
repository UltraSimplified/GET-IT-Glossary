<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Support\TermLinker;
use Illuminate\Contracts\View\View;

/**
 * The embeddable widget: a self-contained page other sites show in an
 * iframe. It deliberately shares nothing with the main app bundle.
 */
class WidgetController extends Controller
{
    public function __invoke(): View
    {
        $terms = Term::query()->live()->orderBy('term')->get(['id', 'term', 'definition'])
            ->map(fn (Term $term): array => [
                'term' => $term->term,
                'definition' => TermLinker::strong($term->definition),
            ]);

        return view('widget', ['terms' => $terms]);
    }
}
