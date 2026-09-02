<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckMonthlyPostLimit
{
    public function handle(Request $request, Closure $next)
    {
        $workspace = $request->user()->currentWorkspace;
        $plan = $workspace->plan;

        $monthlyPostsCount = $workspace->posts()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($monthlyPostsCount >= $plan->max_posts_per_month) {
            return redirect()->route('subscription.upgrade')
                ->with('error', "Monthly posting limit of {$plan->max_posts_per_month} reached! Upgrade to schedule more.");
        }

        return $next($request);
    }
}