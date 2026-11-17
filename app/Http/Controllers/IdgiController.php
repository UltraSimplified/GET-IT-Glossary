<?php

namespace App\Http\Controllers;

use App\Models\Term;
use Illuminate\Http\JsonResponse;

/**
 * "I don't get it": readers tell editors a definition did not help.
 */
class IdgiController extends Controller
{
    public function __invoke(Term $term): JsonResponse
    {
        // Counted without touching updated_at, which records editorial changes.
        Term::query()->toBase()->where('id', $term->id)->increment('IDGI');

        return response()->json(['ok' => true]);
    }
}
