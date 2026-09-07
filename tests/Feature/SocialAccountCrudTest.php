<?php

use App\Models\SocialAccount;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from social accounts index', function () {
    $this->get(route('social-accounts.index'))->assertRedirect(route('login'));
});

test('index lists accounts for the current workspace and management flag', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();

    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'My Page',
    ]);

    $otherUser = User::factory()->create();
    $otherWorkspace = $otherUser->ensureCurrentWorkspace();
    SocialAccount::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'name' => 'Foreign Page',
    ]);

    $this->actingAs($user)
        ->get(route('social-accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SocialAccounts/Index')
            ->has('accounts', 1)
            ->where('accounts.0.id', $account->id)
            ->where('canManage', true)
            ->has('providers', 3)
        );
});

test('workspace admin can toggle social account active state', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();
    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->patch(route('social-accounts.update', $account), [
            'is_active' => false,
        ])
        ->assertRedirect(route('social-accounts.index'));

    expect($account->fresh()->is_active)->toBeFalse();
});

test('editor cannot toggle social account', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();
    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
        'is_active' => true,
    ]);

    $editor = User::factory()->create();
    $workspace->members()->attach($editor->id, ['role' => 'editor']);
    $editor->forceFill(['current_workspace_id' => $workspace->id])->save();

    $this->actingAs($editor)
        ->patch(route('social-accounts.update', $account), [
            'is_active' => false,
        ])
        ->assertForbidden();

    expect($account->fresh()->is_active)->toBeTrue();
});

test('workspace admin can disconnect a social account', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();
    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($user)
        ->delete(route('social-accounts.destroy', $account))
        ->assertRedirect(route('social-accounts.index'));

    expect(SocialAccount::query()->find($account->id))->toBeNull();
});

test('cannot update or destroy an account from another workspace', function () {
    $user = User::factory()->create();
    $user->ensureCurrentWorkspace();

    $otherUser = User::factory()->create();
    $otherWorkspace = $otherUser->ensureCurrentWorkspace();
    $foreign = SocialAccount::factory()->create([
        'workspace_id' => $otherWorkspace->id,
    ]);

    $this->actingAs($user)
        ->patch(route('social-accounts.update', $foreign), [
            'is_active' => false,
        ])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('social-accounts.destroy', $foreign))
        ->assertNotFound();
});

test('invalid oauth provider returns not found', function () {
    $user = User::factory()->create();
    $user->ensureCurrentWorkspace();

    $this->actingAs($user)
        ->get(route('social.redirect', 'instagram'))
        ->assertNotFound();
});

test('editor cannot start oauth redirect', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $editor = User::factory()->create();
    $workspace->members()->attach($editor->id, ['role' => 'editor']);
    $editor->forceFill(['current_workspace_id' => $workspace->id])->save();

    $this->actingAs($editor)
        ->get(route('social.redirect', 'facebook'))
        ->assertForbidden();
});
