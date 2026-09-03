<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'current_workspace_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')->withPivot('role')->withTimestamps();
    }

    public function ensureCurrentWorkspace(): Workspace
    {
        if ($this->current_workspace_id !== null) {
            $workspace = $this->currentWorkspace;

            if ($workspace !== null) {
                return $workspace;
            }
        }

        $name = "{$this->name}'s Workspace";
        $baseSlug = Str::slug($name) ?: 'workspace';
        $slug = $baseSlug;
        $suffix = 1;

        while (Workspace::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        $workspace = Workspace::query()->create([
            'owner_id' => $this->id,
            'name' => $name,
            'slug' => $slug,
        ]);

        $this->workspaces()->attach($workspace->id, ['role' => 'owner']);

        $this->forceFill(['current_workspace_id' => $workspace->id])->save();

        return $workspace;
    }

    public function workspaceRole(Workspace $workspace): ?string
    {
        $membership = $this->workspaces()
            ->where('workspaces.id', $workspace->id)
            ->first();

        return $membership?->pivot?->role;
    }

    public function isWorkspaceMember(Workspace $workspace): bool
    {
        return $this->workspaceRole($workspace) !== null;
    }

    public function isWorkspaceAdmin(Workspace $workspace): bool
    {
        return in_array($this->workspaceRole($workspace), ['owner', 'admin'], true);
    }

    public function isWorkspaceOwner(Workspace $workspace): bool
    {
        return $this->workspaceRole($workspace) === 'owner';
    }
}
