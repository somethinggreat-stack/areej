<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out anyone whose login has been switched off.
 *
 * Sign-in already refuses a deactivated account, but someone who was signed in
 * when they were deactivated would otherwise keep their session until it
 * expired. This runs on every dashboard request so switching an account off
 * takes effect on that person's very next click.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('This account has been deactivated.'),
            ]);
        }

        return $next($request);
    }
}
