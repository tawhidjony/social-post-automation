<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSocialAccountLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $request->user()->currentWorkspace;

        if ($workspace?->plan && ! $workspace->canAddSocialAccount()) {
            return redirect()->route('subscription.upgrade')
                ->with('error', "You have reached the maximum social accounts limit ({$workspace->plan->max_social_accounts}) for your plan.");
        }

        return $next($request);
    }
}
