<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController as PublicPageController;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Content page management (administrators only).
 */
class PageController extends Controller
{
    public function index(): Response
    {
        $pages = Page::query()->orderBy('menu')->orderBy('position')->orderBy('title')->get()
            ->map(fn (Page $page): array => [
                'id' => $page->id,
                'title' => html_entity_decode(strip_tags($page->title), ENT_QUOTES | ENT_HTML5),
                'excerpt' => Str::words(Str::squish(html_entity_decode(strip_tags(str_replace('<', ' <', $page->body)), ENT_QUOTES | ENT_HTML5)), 12),
                'slug' => $page->slug,
                // Pages without an address are not reachable on the site.
                'path' => $page->slug === '' ? null : $page->path(),
                'menu' => $page->menu,
            ]);

        return Inertia::render('admin/pages/Index', ['pages' => $pages]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/pages/Create');
    }

    public function store(PageRequest $request): RedirectResponse
    {
        Page::query()->create([...$request->validated(), 'updated_by' => $request->user()->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page created.')]);

        return to_route('pages.index');
    }

    /**
     * Preview a page as visitors see it.
     */
    public function show(Page $page): Response
    {
        return Inertia::render('Page', [
            'letter' => null,
            'page' => PublicPageController::present($page),
            'form' => null,
            'sent' => false,
            'canEdit' => true,
        ]);
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('admin/pages/Edit', [
            'page' => [
                ...$page->only(['id', 'body', 'slug', 'menu', 'position']),
                // Old titles were stored as "<h1>Title</h1>"; the editor works with plain text.
                'title' => html_entity_decode(strip_tags($page->title), ENT_QUOTES | ENT_HTML5),
                'path' => $page->slug === '' ? null : $page->path(),
            ],
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->update([...$request->validated(), 'updated_by' => $request->user()->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page updated.')]);

        return to_route('pages.index');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page deleted.')]);

        return to_route('pages.index');
    }
}
