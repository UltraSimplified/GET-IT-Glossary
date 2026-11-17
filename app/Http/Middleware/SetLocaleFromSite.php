<?php

namespace App\Http\Middleware;

use App\Support\CurrentSite;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows the interface in the site's language. Strings a language has no
 * translation for fall back to English (APP_FALLBACK_LOCALE).
 */
class SetLocaleFromSite
{
    public function __construct(private readonly CurrentSite $current) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->current->site()->locale);

        return $next($request);
    }
}
