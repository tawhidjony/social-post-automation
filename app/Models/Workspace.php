<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

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
     * Get all subscriptions for the workspace.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the latest active subscription for the workspace.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('status', 'active'),
        );
    }

    /**
     * Cancel active subscriptions and switch the workspace to the given plan.
     */
    public function changePlan(Plan $plan): Subscription
    {
        return DB::transaction(function () use ($plan): Subscription {
            $this->subscriptions()
                ->where('status', 'active')
                ->update([
                    'status' => 'canceled',
                    'ends_at' => now(),
                ]);

            $subscription = $this->subscriptions()->create([
                'plan_id' => $plan->id,
                'stripe_subscription_id' => null,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => null,
            ]);

            $this->update(['current_plan_id' => $plan->id]);

            return $subscription;
        });
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
