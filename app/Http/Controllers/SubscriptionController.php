<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePlanRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->renderSubscriptionPage($request, 'Subscription/Index');
    }

    public function upgrade(Request $request): Response
    {
        return $this->renderSubscriptionPage($request, 'Subscription/Upgrade', [
            'error' => $request->session()->get('error'),
        ]);
    }

    public function store(ChangePlanRequest $request): RedirectResponse
    {
        $workspace = $request->user()->ensureCurrentWorkspace();
        $plan = Plan::query()->findOrFail($request->validated('plan_id'));

        if ($workspace->current_plan_id === $plan->id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Your workspace is already on this plan.'),
            ]);

            return redirect()->route('subscription.index');
        }

        $workspace->changePlan($plan);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Switched to the :plan plan.', ['plan' => $plan->name]),
        ]);

        return redirect()->route('subscription.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->ensureCurrentWorkspace();

        abort_unless($user->isWorkspaceAdmin($workspace), 403);

        $freePlan = Plan::query()->where('slug', 'free')->firstOrFail();

        if ($workspace->current_plan_id === $freePlan->id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Your workspace is already on the Free plan.'),
            ]);

            return redirect()->route('subscription.index');
        }

        $workspace->changePlan($freePlan);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Subscription canceled. Your workspace is now on the Free plan.'),
        ]);

        return redirect()->route('subscription.index');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function renderSubscriptionPage(Request $request, string $component, array $extra = []): Response
    {
        $user = $request->user();
        $workspace = $user->ensureCurrentWorkspace();
        $workspace->load(['plan', 'subscription.plan']);

        return Inertia::render($component, [
            ...$extra,
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ],
            'canManage' => $user->isWorkspaceAdmin($workspace),
            'currentPlan' => $this->planPayload($workspace->plan),
            'subscription' => $this->subscriptionPayload($workspace->subscription),
            'usage' => $this->usagePayload($workspace),
            'plans' => Plan::query()
                ->orderBy('price')
                ->get()
                ->map(fn (Plan $plan) => $this->planPayload($plan))
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function planPayload(?Plan $plan): ?array
    {
        if ($plan === null) {
            return null;
        }

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'price' => (float) $plan->price,
            'max_social_accounts' => $plan->max_social_accounts,
            'max_posts_per_month' => $plan->max_posts_per_month,
            'max_members' => $plan->max_members,
            'has_analytics' => $plan->has_analytics,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function subscriptionPayload(?Subscription $subscription): ?array
    {
        if ($subscription === null) {
            return null;
        }

        return [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'plan' => $this->planPayload($subscription->plan),
        ];
    }

    /**
     * @return array{social_accounts: int, posts_this_month: int, members: int}
     */
    private function usagePayload(Workspace $workspace): array
    {
        return [
            'social_accounts' => $workspace->socialAccounts()->count(),
            'posts_this_month' => $workspace->posts()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'members' => $workspace->members()->count(),
        ];
    }
}
