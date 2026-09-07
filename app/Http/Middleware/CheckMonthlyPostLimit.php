<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMonthlyPostLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $request->user()->currentWorkspace;

        if ($workspace?->plan && ! $workspace->canSchedulePost()) {
            return redirect()->route('subscription.upgrade')
                ->with('error', "Monthly posting limit of {$workspace->plan->max_posts_per_month} reached! Upgrade to schedule more.");
        }

        return $next($request);
    }
}
