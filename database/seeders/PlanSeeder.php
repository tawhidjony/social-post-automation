<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'price' => 0.00,
                'max_social_accounts' => 2,
                'max_posts_per_month' => 10,
                'max_members' => 1,
                'has_analytics' => false,
            ],
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'price' => 29.00,
                'max_social_accounts' => 10,
                'max_posts_per_month' => 500,
                'max_members' => 5,
                'has_analytics' => true,
            ],
        );
    }
}
