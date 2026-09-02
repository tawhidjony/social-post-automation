<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => function () {
                return User::factory()->create()->ensureCurrentWorkspace()->id;
            },
            'provider' => 'facebook',
            'provider_account_id' => (string) fake()->unique()->numerify('##########'),
            'name' => fake()->company(),
            'username' => fake()->userName(),
            'avatar_url' => fake()->imageUrl(40, 40),
            'access_token' => 'test-access-token',
            'refresh_token' => null,
            'token_expires_at' => null,
            'is_active' => true,
        ];
    }
}
