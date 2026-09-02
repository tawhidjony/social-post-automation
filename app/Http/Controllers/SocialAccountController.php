<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\SocialiteManagerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class SocialAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $workspaceId = $request->user()->ensureCurrentWorkspace()->id;

        $accounts = SocialAccount::where('workspace_id', $workspaceId)
            ->select(['id', 'provider', 'provider_account_id', 'name', 'username', 'avatar_url', 'is_active', 'created_at'])
            ->get();

        return Inertia::render('SocialAccounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    public function redirect(Request $request, string $provider)
    {
        $request->user()->ensureCurrentWorkspace();

        $scopes = match ($provider) {
            'facebook' => ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list'],
            'linkedin' => ['openid', 'profile', 'w_member_social'],
            'twitter' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
            default => [],
        };

        return Socialite::driver($provider)
            ->scopes($scopes)
            ->redirect();
    }

    public function callback(string $provider, Request $request, SocialiteManagerService $service)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();
        $workspaceId = $request->user()->ensureCurrentWorkspace()->id;

        if ($provider === 'facebook') {
            $service->handleFacebookPages($socialUser, $workspaceId);
        } else {
            SocialAccount::updateOrCreate(
                [
                    'workspace_id' => $workspaceId,
                    'provider' => $provider,
                    'provider_account_id' => $socialUser->getId(),
                ],
                [
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'username' => $socialUser->getNickname(),
                    'avatar_url' => $socialUser->getAvatar(),
                    'access_token' => $socialUser->token,
                    'refresh_token' => $socialUser->refreshToken ?? null,
                    'token_expires_at' => isset($socialUser->expiresIn) ? now()->addSeconds($socialUser->expiresIn) : null,
                    'is_active' => true,
                ]
            );
        }

        return redirect()->route('social-accounts.index')->with('success', 'Social account connected!');
    }
}
