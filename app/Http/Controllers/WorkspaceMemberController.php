<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceMemberRequest;
use App\Http\Requests\UpdateWorkspaceMemberRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response
    {
        $user = $request->user();
        abort_unless($user->isWorkspaceMember($workspace), 404);

        $members = $workspace->members()
            ->orderBy('name')
            ->get()
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
            ]);

        return Inertia::render('Workspaces/Members', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'members' => $members,
            'canManage' => $user->isWorkspaceAdmin($workspace),
        ]);
    }

    public function store(StoreWorkspaceMemberRequest $request, Workspace $workspace): RedirectResponse
    {
        $validated = $request->validated();
        $invitee = User::query()->where('email', $validated['email'])->firstOrFail();

        if ($invitee->isWorkspaceMember($workspace)) {
            return redirect()
                ->route('workspaces.members.index', $workspace)
                ->with('error', 'User is already a member of this workspace.');
        }

        $workspace->members()->attach($invitee->id, ['role' => $validated['role']]);

        return redirect()
            ->route('workspaces.members.index', $workspace)
            ->with('success', 'Member invited.');
    }

    public function update(
        UpdateWorkspaceMemberRequest $request,
        Workspace $workspace,
        User $user
    ): RedirectResponse {
        abort_unless($user->isWorkspaceMember($workspace), 404);

        $currentRole = $user->workspaceRole($workspace);

        if ($currentRole === 'owner') {
            return redirect()
                ->route('workspaces.members.index', $workspace)
                ->with('error', 'Cannot change the owner role.');
        }

        $workspace->members()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        return redirect()
            ->route('workspaces.members.index', $workspace)
            ->with('success', 'Member role updated.');
    }

    public function destroy(Request $request, Workspace $workspace, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isWorkspaceAdmin($workspace), 403);
        abort_unless($user->isWorkspaceMember($workspace), 404);

        $role = $user->workspaceRole($workspace);

        if ($role === 'owner') {
            return redirect()
                ->route('workspaces.members.index', $workspace)
                ->with('error', 'Cannot remove the workspace owner.');
        }

        $workspace->members()->detach($user->id);

        if ($user->current_workspace_id === $workspace->id) {
            $fallback = $user->workspaces()->orderBy('workspaces.id')->first();
            $user->forceFill(['current_workspace_id' => $fallback?->id])->save();
        }

        return redirect()
            ->route('workspaces.members.index', $workspace)
            ->with('success', 'Member removed.');
    }
}
