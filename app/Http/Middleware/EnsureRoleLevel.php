<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route behind a minimum role level.
 *
 * Levels rather than named permissions, because the client described access as
 * a ladder: owner sees everything, management sees everything bar owner
 * settings, the kitchen manager counts stock but never sees pay, staff see only
 * themselves. A level comparison expresses that directly and cannot drift out
 * of step with the roles table.
 *
 *   ->middleware('role:50')   kitchen manager and above
 *   ->middleware('role:80')   management and above
 */
class EnsureRoleLevel
{
    public function handle(Request $request, Closure $next, int|string $level): Response
    {
        $user = $request->user();

        abort_if($user === null, 401);
        abort_unless($user->is_active, 403, __('This account has been deactivated.'));
        abort_unless($user->hasRoleLevel((int) $level), 403, __('You do not have access to this area.'));

        return $next($request);
    }
}
