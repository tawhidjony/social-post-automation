<?php

namespace App\Http\Controllers;

use App\Services\SocialiteManagerService;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;
use Inertia\Response;

class SocialAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $workspaceId = $request->user()->current_workspace_id;

        $accounts = SocialAccount::where('workspace_id', $workspaceId)
            ->select(['id', 'provider', 'provider_account_id', 'name', 'username', 'avatar_url', 'is_active', 'created_at'])
            ->get();

        return Inertia::render('SocialAccounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    public function redirect(string $provider)
    {
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
        $workspaceId = $request->user()->current_workspace_id;

        // // ১. ওয়ার্কস্পেস আইডি না থাকলে এরর মেসেজ দিয়ে ফেরত পাঠান
        // if (!$workspaceId) {
        //     return redirect()->route('social-accounts.index')
        //         ->with('error', 'সোশাল অ্যাকাউন্ট যুক্ত করার আগে দয়া করে একটি ওয়ার্কস্পেস সিলেক্ট করুন।');
        // }

        // ২. নিশ্চিত হওয়ার জন্য মানটিকে integer-এ কনভার্ট (cast) করে নিন
        $workspaceId = (int) $workspaceId;

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