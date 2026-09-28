<?php

use App\Models\Plan;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
});

test('guests are redirected from subscription pages', function () {
    $this->get(route('subscription.index'))->assertRedirect(route('login'));
    $this->get(route('subscription.upgrade'))->assertRedirect(route('login'));
});

test('members can view subscription index with plan and usage', function () {
    $user = User::factory()->withActivePlan()->create();
    $workspace = $user->currentWorkspace;

    $this->actingAs($user)
        ->get(route('subscription.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Subscription/Index')
            ->where('workspace.id', $workspace->id)
            ->where('canManage', true)
            ->where('currentPlan.slug', 'free')
            ->where('subscription.status', 'active')
            ->has('plans', 2)
            ->where('usage.social_accounts', 0)
        );
});

test('workspace creation does not assign a plan', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();

    expect($workspace->current_plan_id)->toBeNull()
        ->and($workspace->subscription)->toBeNull()
        ->and($workspace->hasActivePlan())->toBeFalse();
});

test('users without a plan are redirected to upgrade from gated routes', function () {
    $user = User::factory()->create();
    $user->ensureCurrentWorkspace();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.upgrade'));

    $this->actingAs($user)
        ->get(route('subscription.index'))
        ->assertRedirect(route('subscription.upgrade'));

    $this->actingAs($user)
        ->get(route('subscription.upgrade'))
        ->assertOk();
});

test('users without a plan can choose free and unlock the app', function () {
    $user = User::factory()->create();
    $user->ensureCurrentWorkspace();
    $freePlan = Plan::query()->where('slug', 'free')->firstOrFail();

    $this->actingAs($user)
        ->post(route('subscription.store'), [
            'plan_id' => $freePlan->id,
        ])
        ->assertRedirect(route('subscription.index'));

    expect($user->fresh()->currentWorkspace->hasActivePlan())->toBeTrue();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('admin can upgrade from free to pro', function () {
    $user = User::factory()->withActivePlan()->create();
    $workspace = $user->currentWorkspace;
    $freeSubscriptionId = $workspace->subscription->id;
    $proPlan = Plan::query()->where('slug', 'pro')->firstOrFail();

    $this->actingAs($user)
        ->post(route('subscription.store'), [
            'plan_id' => $proPlan->id,
        ])
        ->assertRedirect(route('subscription.index'));

    $workspace->refresh();

    expect($workspace->current_plan_id)->toBe($proPlan->id)
        ->and(Subscription::query()->find($freeSubscriptionId)->status)->toBe('canceled')
        ->and($workspace->subscription)->not->toBeNull()
        ->and($workspace->subscription->plan_id)->toBe($proPlan->id)
        ->and($workspace->subscription->status)->toBe('active')
        ->and($workspace->subscription->stripe_subscription_id)->toBeNull();
});

test('admin can cancel a paid plan back to free', function () {
    $user = User::factory()->withActivePlan()->create();
    $workspace = $user->currentWorkspace;
    $proPlan = Plan::query()->where('slug', 'pro')->firstOrFail();
    $freePlan = Plan::query()->where('slug', 'free')->firstOrFail();

    $workspace->changePlan($proPlan);

    $this->actingAs($user)
        ->delete(route('subscription.destroy'))
        ->assertRedirect(route('subscription.index'));

    $workspace->refresh();

    expect($workspace->current_plan_id)->toBe($freePlan->id)
        ->and($workspace->subscription->plan_id)->toBe($freePlan->id)
        ->and($workspace->subscription->status)->toBe('active');
});

test('editor cannot change or cancel the subscription', function () {
    $owner = User::factory()->withActivePlan()->create();
    $workspace = $owner->currentWorkspace;
    $proPlan = Plan::query()->where('slug', 'pro')->firstOrFail();

    $editor = User::factory()->create();
    $workspace->members()->attach($editor->id, ['role' => 'editor']);
    $editor->forceFill(['current_workspace_id' => $workspace->id])->save();

    $this->actingAs($editor)
        ->post(route('subscription.store'), [
            'plan_id' => $proPlan->id,
        ])
        ->assertForbidden();

    $this->actingAs($editor)
        ->delete(route('subscription.destroy'))
        ->assertForbidden();

    expect($workspace->fresh()->current_plan_id)->toBe(
        Plan::query()->where('slug', 'free')->value('id'),
    );
});

test('social account limit redirects to subscription upgrade', function () {
    $user = User::factory()->withActivePlan()->create();
    $workspace = $user->currentWorkspace;
    $limit = $workspace->plan->max_social_accounts;

    SocialAccount::factory()->count($limit)->create([
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($user)
        ->get(route('social.redirect', ['provider' => 'facebook']))
        ->assertRedirect(route('subscription.upgrade'))
        ->assertSessionHas('error');
});

test('monthly post limit redirects to subscription upgrade', function () {
    $user = User::factory()->withActivePlan()->create();
    $workspace = $user->currentWorkspace;
    $limit = $workspace->plan->max_posts_per_month;
    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    Post::factory()->count($limit)->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($user)
        ->post(route('posts.store'), [
            'social_account_ids' => [$account->id],
            'content' => 'Over the limit',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect(route('subscription.upgrade'))
        ->assertSessionHas('error');
});
