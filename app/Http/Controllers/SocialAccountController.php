<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSocialAccountRequest;
use App\Models\SocialAccount;
use App\Services\SocialiteManagerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class SocialAccountController extends Controller
{
    /**
     * @var list<string>
     */
    private const Providers = ['facebook', 'linkedin', 'twitter'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $workspace = $user->ensureCurrentWorkspace();

        $accounts = SocialAccount::query()
            ->where('workspace_id', $workspace->id)
            ->select(['id', 'provider', 'provider_account_id', 'name', 'username', 'avatar_url', 'is_active', 'created_at'])
            ->get();

        return Inertia::render('SocialAccounts/Index', [
            'accounts' => $accounts,
            'canManage' => $user->isWorkspaceAdmin($workspace),
            'providers' => self::Providers,
        ]);
    }

    public function redirect(Request $request, string $provider)
    {
        $this->ensureValidProvider($provider);

        $user = $request->user();
        $workspace = $user->ensureCurrentWorkspace();
        abort_unless($user->isWorkspaceAdmin($workspace), 403);

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
        $this->ensureValidProvider($provider);

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

    public function update(UpdateSocialAccountRequest $request, SocialAccount $socialAccount): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->ensureCurrentWorkspace();

        abort_unless($socialAccount->workspace_id === $workspace->id, 404);
        abort_unless($user->isWorkspaceAdmin($workspace), 403);

        $socialAccount->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('social-accounts.index')
            ->with('success', 'Social account updated.');
    }

    public function destroy(Request $request, SocialAccount $socialAccount): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->ensureCurrentWorkspace();

        abort_unless($socialAccount->workspace_id === $workspace->id, 404);
        abort_unless($user->isWorkspaceAdmin($workspace), 403);

        $socialAccount->delete();

        return redirect()
            ->route('social-accounts.index')
            ->with('success', 'Social account disconnected.');
    }

    private function ensureValidProvider(string $provider): void
    {
        abort_unless(in_array($provider, self::Providers, true), 404);
    }
}
