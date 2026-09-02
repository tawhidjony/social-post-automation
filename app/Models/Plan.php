<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'max_social_accounts',
        'max_posts_per_month',
        'max_members',
        'has_analytics',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_social_accounts' => 'integer',
        'max_posts_per_month' => 'integer',
        'max_members' => 'integer',
        'has_analytics' => 'boolean',
    ];

    /**
     * Get all workspaces associated with this plan.
     */
    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'current_plan_id');
    }

    /**
     * Get all active subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}