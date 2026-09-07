<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'current_plan_id',
    ];

    /**
     * Get the owner of the workspace.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the plan assigned to this workspace.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    /**
     * Get active subscription for the workspace.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Get members/users belonging to this workspace.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get connected social accounts.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Get posts created within this workspace.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Check if workspace can add more social accounts based on Plan limit.
     */
    public function canAddSocialAccount(): bool
    {
        if (! $this->plan) {
            return false;
        }

        return $this->socialAccounts()->count() < $this->plan->max_social_accounts;
    }

    /**
     * Check if workspace can schedule more posts this current month.
     */
    public function canSchedulePost(): bool
    {
        if (! $this->plan) {
            return false;
        }

        $currentMonthPosts = $this->posts()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return $currentMonthPosts < $this->plan->max_posts_per_month;
    }

    /**
     * Check if analytics feature is unlocked for this workspace.
     */
    public function hasAnalyticsAccess(): bool
    {
        return $this->plan ? $this->plan->has_analytics : false;
    }
}
