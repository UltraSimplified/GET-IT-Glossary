<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdgiReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Editors handling what readers said after pressing "I don't get it".
 */
class IdgiReplyController extends Controller
{
    /**
     * Mark a reply dealt with (recording who and when), or new again.
     */
    public function update(Request $request, IdgiReply $reply): RedirectResponse
    {
        Gate::authorize('update', $reply);

        $handled = $request->validate(['handled' => ['required', 'boolean']])['handled'];

        $reply->update((bool) $handled
            ? ['handled_at' => now(), 'handled_by' => $request->user()?->id]
            : ['handled_at' => null, 'handled_by' => null]);

        return back();
    }

    public function destroy(IdgiReply $reply): RedirectResponse
    {
        Gate::authorize('delete', $reply);

        $reply->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reply deleted.')]);

        return back();
    }
}
