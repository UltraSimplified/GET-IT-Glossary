<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public content pages. The same page can be reached under /about,
 * /the-api and /get-in-touch; the three contact pages also carry a form.
 */
class PageController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $page = Page::query()->where('slug', $slug)->first();

        if (! $page) {
            throw new NotFoundHttpException;
        }

        return $this->render($request, $page, null);
    }

    public function contact(Request $request, string $slug): Response
    {
        $page = Page::query()->where('slug', $slug)->first();
        $form = in_array($slug, Page::FORM_SLUGS, true) ? $slug : null;

        if (! $page && ! $form) {
            throw new NotFoundHttpException;
        }

        return $this->render($request, $page, $form);
    }

    private function render(Request $request, ?Page $page, ?string $form): Response
    {
        return Inertia::render('Page', [
            'letter' => null,
            'page' => $page ? self::present($page) : null,
            'form' => $form,
            'sent' => (bool) $request->session()->get('contact.sent', false),
            'canEdit' => (bool) $request->user()?->is_admin,
        ]);
    }

    /**
     * Page titles and bodies are written by administrators in the page
     * editor and are rendered as HTML.
     *
     * @return array<string, mixed>
     */
    public static function present(Page $page): array
    {
        return [
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => html_entity_decode(strip_tags($page->title), ENT_QUOTES | ENT_HTML5),
            'body_html' => $page->body,
        ];
    }
}
