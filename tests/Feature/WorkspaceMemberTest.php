<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('members can view workspace members list', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $this->actingAs($owner)
        ->get(route('workspaces.members.index', $workspace))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Workspaces/Members')
            ->has('members', 1)
            ->where('canManage', true)
        );
});

test('non members cannot view workspace members', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $stranger = User::factory()->create();
    $stranger->ensureCurrentWorkspace();

    $this->actingAs($stranger)
        ->get(route('workspaces.members.index', $workspace))
        ->assertNotFound();
});

test('admins can invite an existing user as editor', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $invitee = User::factory()->create([
        'email' => 'invitee@example.com',
    ]);
    $invitee->ensureCurrentWorkspace();

    $this->actingAs($owner)
        ->post(route('workspaces.members.store', $workspace), [
            'email' => 'invitee@example.com',
            'role' => 'editor',
        ])
        ->assertRedirect(route('workspaces.members.index', $workspace));

    expect($invitee->fresh()->isWorkspaceMember($workspace))->toBeTrue()
        ->and($invitee->workspaceRole($workspace))->toBe('editor');
});

test('editors cannot invite members', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $editor = User::factory()->create();
    $workspace->members()->attach($editor->id, ['role' => 'editor']);

    $invitee = User::factory()->create();

    $this->actingAs($editor)
        ->post(route('workspaces.members.store', $workspace), [
            'email' => $invitee->email,
            'role' => 'editor',
        ])
        ->assertForbidden();
});

test('admins can update a member role', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $member = User::factory()->create();
    $workspace->members()->attach($member->id, ['role' => 'editor']);

    $this->actingAs($owner)
        ->put(route('workspaces.members.update', [$workspace, $member]), [
            'role' => 'admin',
        ])
        ->assertRedirect(route('workspaces.members.index', $workspace));

    expect($member->workspaceRole($workspace))->toBe('admin');
});

test('cannot change the owner role', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $admin = User::factory()->create();
    $workspace->members()->attach($admin->id, ['role' => 'admin']);

    $this->actingAs($admin)
        ->from(route('workspaces.members.index', $workspace))
        ->put(route('workspaces.members.update', [$workspace, $owner]), [
            'role' => 'editor',
        ])
        ->assertRedirect(route('workspaces.members.index', $workspace))
        ->assertSessionHas('error');

    expect($owner->workspaceRole($workspace))->toBe('owner');
});

test('admins can remove a non owner member', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $member = User::factory()->create();
    $memberOwn = $member->ensureCurrentWorkspace();
    $workspace->members()->attach($member->id, ['role' => 'editor']);
    $member->forceFill(['current_workspace_id' => $workspace->id])->save();

    $this->actingAs($owner)
        ->delete(route('workspaces.members.destroy', [$workspace, $member]))
        ->assertRedirect(route('workspaces.members.index', $workspace));

    expect($member->fresh()->isWorkspaceMember($workspace))->toBeFalse()
        ->and($member->fresh()->current_workspace_id)->toBe($memberOwn->id);
});

test('cannot remove the workspace owner', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $admin = User::factory()->create();
    $workspace->members()->attach($admin->id, ['role' => 'admin']);

    $this->actingAs($admin)
        ->from(route('workspaces.members.index', $workspace))
        ->delete(route('workspaces.members.destroy', [$workspace, $owner]))
        ->assertRedirect(route('workspaces.members.index', $workspace))
        ->assertSessionHas('error');

    expect($owner->fresh()->isWorkspaceMember($workspace))->toBeTrue();
});
