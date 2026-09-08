<?php

use App\Models\User;
use Database\Seeders\PlanSeeder;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->seed(PlanSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('subscription.upgrade', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->current_workspace_id)->not->toBeNull()
        ->and($user->workspaces)->toHaveCount(1)
        ->and($user->workspaces->first()->pivot->role)->toBe('owner')
        ->and($user->workspaces->first()->current_plan_id)->toBeNull()
        ->and($user->workspaces->first()->hasActivePlan())->toBeFalse();
});
