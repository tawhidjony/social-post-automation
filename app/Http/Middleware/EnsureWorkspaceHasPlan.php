<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceHasPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $workspace = $user->ensureCurrentWorkspace();

        if (! $workspace->hasActivePlan()) {
            return redirect()->route('subscription.upgrade');
        }

        return $next($request);
    }
}
