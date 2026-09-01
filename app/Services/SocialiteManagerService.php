<?php

namespace App\Services;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialiteManagerService
{
    public function handleFacebookPages(SocialiteUser $socialUser, int $workspaceId): void
    {
        $userAccessToken = $socialUser->token;

        $response = Http::get("https://graph.facebook.com/v19.0/me/accounts", [
            'access_token' => $userAccessToken,
            'fields' => 'id,name,username,picture,access_token',
        ]);

        if ($response->successful()) {
            $pages = $response->json('data', []);

            foreach ($pages as $page) {
                SocialAccount::updateOrCreate(
                    [
                        'workspace_id' => $workspaceId,
                        'provider' => 'facebook',
                        'provider_account_id' => $page['id'],
                    ],
                    [
                        'name' => $page['name'],
                        'username' => $page['username'] ?? null,
                        'avatar_url' => $page['picture']['data']['url'] ?? null,
                        'access_token' => $page['access_token'],
                        'token_expires_at' => null,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}