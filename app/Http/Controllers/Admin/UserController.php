<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Term;
use App\Models\TermEditor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * User management (administrators only).
 *
 * A user's role follows from their data: administrators can do everything,
 * editors can edit the terms assigned to them, and everyone else is a
 * contributor who can comment.
 */
class UserController extends Controller
{
    public function index(): Response
    {
        $termCounts = TermEditor::query()
            ->selectRaw('user_id, COUNT(DISTINCT term_id) AS total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $users = User::query()->orderBy('last_name')->orderBy('first_name')->get()
            ->map(function (User $user) use ($termCounts): array {
                $terms = (int) ($termCounts[$user->id] ?? 0);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => match (true) {
                        $user->is_admin => 'Admin',
                        $terms > 0 => 'Editor',
                        default => 'Contributor',
                    },
                    'terms' => $terms,
                ];
            });

        return Inertia::render('admin/users/Index', ['users' => $users]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/Create');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = new User($request->safe()->only(['first_name', 'last_name', 'username', 'email', 'password']));
        $user->is_admin = (bool) ($data['is_admin'] ?? false);
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User added.')]);

        return to_route('users.index');
    }

    public function edit(User $user): Response
    {
        $assigned = TermEditor::query()->where('user_id', $user->id)->pluck('term_id')->all();

        // Terms this user edits come first, then the rest alphabetically.
        $terms = Term::query()->live()->orderBy('term')->get(['id', 'master_id', 'term'])
            ->map(fn (Term $term): array => [
                'master_id' => $term->master_id,
                'term' => $term->term,
                'assigned' => in_array($term->master_id, $assigned, true),
            ])
            ->sortByDesc('assigned')
            ->values();

        return Inertia::render('admin/users/Edit', [
            'user' => $user->only(['id', 'first_name', 'last_name', 'username', 'email', 'is_admin', 'name']),
            'terms' => $terms,
            'isSelf' => $user->is(request()->user()),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $user, $data): void {
            $user->fill($request->safe()->only(['first_name', 'last_name', 'username', 'email']));

            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
            }

            // Administrators cannot remove their own admin rights by accident.
            if (! $user->is($request->user())) {
                $user->is_admin = (bool) ($data['is_admin'] ?? false);
            }

            $user->save();

            if ($request->has('terms')) {
                TermEditor::assignTerms($user->id, array_values(array_unique(array_map(intval(...), $data['terms'] ?? []))));
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account here.');

        DB::transaction(function () use ($user): void {
            TermEditor::query()->where('user_id', $user->id)->delete();
            $user->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }
}
