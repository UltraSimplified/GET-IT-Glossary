<?php

namespace App\Http\Controllers;

use App\Models\IdgiReply;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A reader's optional reply after pressing "I don't get it": what didn't
 * make sense. Anonymous, one per click, and only for published versions.
 */
class IdgiReplyController extends Controller
{
    public function __invoke(Request $request, Term $term): JsonResponse
    {
        abort_unless($term->enabled, 404);

        $key = IdgiController::replyKey($term);
        abort_unless($request->session()->has($key), 403, __('interface.idgi-reply-no-click'));

        // People never see the hidden field; whatever fills it in is a bot,
        // so it is told all went well and nothing is kept.
        if (filled($request->input('website'))) {
            $request->session()->forget($key);

            return response()->json(['ok' => true]);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => __('interface.idgi-reply-required'),
            'message.max' => __('interface.idgi-reply-too-long'),
        ]);

        $request->session()->forget($key);

        IdgiReply::query()->create([
            'term_id' => $term->id,
            'master_id' => $term->master_id,
            'message' => $validated['message'],
        ]);

        return response()->json(['ok' => true]);
    }
}
