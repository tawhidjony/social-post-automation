<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSocialAccountLimit
{
    public function handle(Request $request, Closure $next)
    {
        $workspace = $request->user()->currentWorkspace;
        $plan = $workspace->plan;

        $connectedAccountsCount = $workspace->socialAccounts()->count();

        if ($connectedAccountsCount >= $plan->max_social_accounts) {
            return redirect()->route('subscription.upgrade')
                ->with('error', "You have reached the maximum social accounts limit ({$plan->max_social_accounts}) for your plan.");
        }

        return $next($request);
    }
}
