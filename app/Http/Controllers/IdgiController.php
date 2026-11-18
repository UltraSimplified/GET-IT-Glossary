<?php

namespace App\Http\Controllers;

use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "I don't get it": readers tell editors a definition did not help.
 */
class IdgiController extends Controller
{
    /** Session key that lets this visitor send one reply about the version. */
    public static function replyKey(Term $term): string
    {
        return 'idgi.reply.'.$term->id;
    }

    public function __invoke(Request $request, Term $term): JsonResponse
    {
        // Counted without touching updated_at, which records editorial changes.
        Term::query()->toBase()->where('id', $term->id)->increment('IDGI');

        // The click allows one reply about this version, in this session.
        $request->session()->put(self::replyKey($term), true);

        return response()->json(['ok' => true]);
    }
}
