<?php

use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from workspaces index', function () {
    $this->get(route('workspaces.index'))->assertRedirect(route('login'));
});

test('authenticated users can view their workspaces', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();

    $this->actingAs($user)
        ->get(route('workspaces.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Workspaces/Index')
            ->has('workspaces', 1)
            ->where('workspaces.0.id', $workspace->id)
            ->where('workspaces.0.is_current', true)
        );
});

test('authenticated users can create a workspace and switch to it', function () {
    $user = User::factory()->create();
    $user->ensureCurrentWorkspace();

    $response = $this->actingAs($user)->post(route('workspaces.store'), [
        'name' => 'Acme Team',
        'slug' => 'acme-team',
        'switch' => true,
    ]);

    $response->assertRedirect(route('workspaces.index'));

    $workspace = Workspace::query()->where('slug', 'acme-team')->first();

    expect($workspace)->not->toBeNull()
        ->and($workspace->owner_id)->toBe($user->id)
        ->and($user->fresh()->current_workspace_id)->toBe($workspace->id)
        ->and($user->workspaceRole($workspace))->toBe('owner');
});

test('workspace store validation requires name and unique slug', function () {
    $user = User::factory()->create();
    $existing = $user->ensureCurrentWorkspace();

    $this->actingAs($user)
        ->from(route('workspaces.create'))
        ->post(route('workspaces.store'), [
            'name' => '',
            'slug' => $existing->slug,
        ])
        ->assertRedirect(route('workspaces.create'))
        ->assertSessionHasErrors(['name', 'slug']);
});

test('admins can update a workspace', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();

    $this->actingAs($user)
        ->put(route('workspaces.update', $workspace), [
            'name' => 'Renamed Workspace',
            'slug' => 'renamed-workspace',
        ])
        ->assertRedirect(route('workspaces.index'));

    expect($workspace->fresh()->name)->toBe('Renamed Workspace')
        ->and($workspace->fresh()->slug)->toBe('renamed-workspace');
});

test('editors cannot update a workspace', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $editor = User::factory()->create();
    $workspace->members()->attach($editor->id, ['role' => 'editor']);
    $editor->forceFill(['current_workspace_id' => $workspace->id])->save();

    $this->actingAs($editor)
        ->put(route('workspaces.update', $workspace), [
            'name' => 'Hacked',
            'slug' => 'hacked',
        ])
        ->assertForbidden();
});

test('users can switch to a workspace they belong to', function () {
    $user = User::factory()->create();
    $first = $user->ensureCurrentWorkspace();

    $second = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Second',
        'slug' => 'second-ws',
    ]);
    $user->workspaces()->attach($second->id, ['role' => 'owner']);

    $this->actingAs($user)
        ->post(route('workspaces.switch', $second))
        ->assertRedirect(route('workspaces.index'));

    expect($user->fresh()->current_workspace_id)->toBe($second->id)
        ->and($user->fresh()->current_workspace_id)->not->toBe($first->id);
});

test('non members cannot switch to a foreign workspace', function () {
    $user = User::factory()->create();
    $user->ensureCurrentWorkspace();

    $other = User::factory()->create();
    $foreign = $other->ensureCurrentWorkspace();

    $this->actingAs($user)
        ->post(route('workspaces.switch', $foreign))
        ->assertNotFound();
});

test('owner cannot delete their last workspace', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();

    $this->actingAs($user)
        ->from(route('workspaces.index'))
        ->delete(route('workspaces.destroy', $workspace))
        ->assertRedirect(route('workspaces.index'))
        ->assertSessionHas('error');

    expect(Workspace::query()->find($workspace->id))->not->toBeNull();
});

test('owner can delete a non last workspace and current is reassigned', function () {
    $user = User::factory()->create();
    $first = $user->ensureCurrentWorkspace();

    $second = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Extra',
        'slug' => 'extra-ws',
    ]);
    $user->workspaces()->attach($second->id, ['role' => 'owner']);
    $user->forceFill(['current_workspace_id' => $second->id])->save();

    $this->actingAs($user)
        ->delete(route('workspaces.destroy', $second))
        ->assertRedirect(route('workspaces.index'));

    expect(Workspace::query()->find($second->id))->toBeNull()
        ->and($user->fresh()->current_workspace_id)->toBe($first->id);
});

test('non owner cannot destroy a workspace', function () {
    $owner = User::factory()->create();
    $workspace = $owner->ensureCurrentWorkspace();

    $admin = User::factory()->create();
    $workspace->members()->attach($admin->id, ['role' => 'admin']);

    Workspace::query()->create([
        'owner_id' => $owner->id,
        'name' => 'Spare',
        'slug' => 'spare-ws',
    ]);
    $owner->workspaces()->attach(
        Workspace::query()->where('slug', 'spare-ws')->value('id'),
        ['role' => 'owner']
    );

    $this->actingAs($admin)
        ->delete(route('workspaces.destroy', $workspace))
        ->assertForbidden();
});
