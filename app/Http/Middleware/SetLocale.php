<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the chosen interface language. A signed-in user's saved preference
 * wins; otherwise the session choice from the language toggle is used.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? session('locale')
            ?? config('app.locale');

        if (in_array($locale, ['en', 'ur'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
