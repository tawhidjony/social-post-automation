<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $user->ensureCurrentWorkspace();

        $workspaces = $user->workspaces()
            ->with('owner:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'role' => $workspace->pivot->role,
                'is_current' => $workspace->id === $user->current_workspace_id,
                'owner' => $workspace->owner
                    ? ['id' => $workspace->owner->id, 'name' => $workspace->owner->name]
                    : null,
                'can_manage' => in_array($workspace->pivot->role, ['owner', 'admin'], true),
                'can_delete' => $workspace->pivot->role === 'owner',
            ]);

        return Inertia::render('Workspaces/Index', [
            'workspaces' => $workspaces,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Workspaces/Create');
    }

    public function store(StoreWorkspaceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $workspace = Workspace::query()->create([
            'owner_id' => $user->id,
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'current_plan_id' => Plan::query()->where('slug', 'free')->value('id'),
        ]);

        $user->workspaces()->attach($workspace->id, ['role' => 'owner']);

        if ($request->boolean('switch', true)) {
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();
        }

        return redirect()->route('workspaces.index')->with('success', 'Workspace created.');
    }

    public function edit(Request $request, Workspace $workspace): Response
    {
        $this->ensureMember($request->user(), $workspace);
        abort_unless($request->user()->isWorkspaceAdmin($workspace), 403);

        return Inertia::render('Workspaces/Edit', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
        ]);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $validated = $request->validated();

        $workspace->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        return redirect()->route('workspaces.index')->with('success', 'Workspace updated.');
    }

    public function destroy(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $this->ensureMember($user, $workspace);
        abort_unless($user->isWorkspaceOwner($workspace), 403);

        if ($user->workspaces()->count() <= 1) {
            return redirect()
                ->route('workspaces.index')
                ->with('error', 'You cannot delete your last workspace.');
        }

        DB::transaction(function () use ($workspace): void {
            $workspaceId = $workspace->id;
            $memberIds = $workspace->members()->pluck('users.id');

            $workspace->delete();

            User::query()
                ->whereIn('id', $memberIds)
                ->where('current_workspace_id', $workspaceId)
                ->get()
                ->each(function (User $member): void {
                    $fallback = $member->workspaces()->orderBy('workspaces.id')->first();

                    $member->forceFill([
                        'current_workspace_id' => $fallback?->id,
                    ])->save();
                });
        });

        return redirect()->route('workspaces.index')->with('success', 'Workspace deleted.');
    }

    public function switch(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $this->ensureMember($user, $workspace);

        $user->forceFill(['current_workspace_id' => $workspace->id])->save();

        return redirect()->route('workspaces.index')->with('success', 'Switched workspace.');
    }

    private function ensureMember(User $user, Workspace $workspace): void
    {
        abort_unless($user->isWorkspaceMember($workspace), 404);
    }
}
