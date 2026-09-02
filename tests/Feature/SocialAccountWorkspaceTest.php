<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('facebook callback provisions a workspace when current_workspace_id is null', function () {
    $user = User::factory()->create([
        'current_workspace_id' => null,
    ]);

    expect($user->current_workspace_id)->toBeNull();

    Socialite::fake('facebook', SocialiteUser::fake([
        'id' => 'user-fb-1',
        'token' => 'user-access-token',
    ]));

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => '1312683078595520',
                    'name' => 'Auto Post',
                    'username' => null,
                    'picture' => [
                        'data' => [
                            'url' => 'https://example.com/avatar.png',
                        ],
                    ],
                    'access_token' => 'page-access-token',
                ],
            ],
        ]),
    ]);

    $response = $this->actingAs($user)->get(route('social.callback', ['provider' => 'facebook']));

    $response->assertRedirect(route('social-accounts.index'));

    $user->refresh();

    expect($user->current_workspace_id)->not->toBeNull()
        ->and($user->current_workspace_id)->not->toBe(0);

    $account = SocialAccount::query()->first();

    expect($account)->not->toBeNull()
        ->and($account->workspace_id)->toBe($user->current_workspace_id)
        ->and($account->provider)->toBe('facebook')
        ->and($account->provider_account_id)->toBe('1312683078595520')
        ->and($account->name)->toBe('Auto Post');
});
