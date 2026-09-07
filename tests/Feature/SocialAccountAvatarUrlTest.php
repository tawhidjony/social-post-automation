<?php

use App\Models\SocialAccount;
use App\Models\User;

test('social account can store a facebook-length avatar url', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();

    $avatarUrl = 'https://scontent.fdac183-1.fna.fbcdn.net/v/t39.30808-1/790756324_122093805687472842_1677436125279318919_n.png'
        .'?stp=cp0_dst-png_s50x50&_nc_cat=110&ccb=1-7&_nc_sid=f907e8'
        .'&_nc_ohc=fNp6eI7SZXgQ7kNvwG1KF4I&_nc_oc=AdqnI7ju2TiuBZnAduhSofVxknBR3KyPP8Muau0F4iT52OLdJOfJQygPq43REq7biRU'
        .'&_nc_zt=24&_nc_ht=scontent.fdac183-1.fna&edm=AGaHXAAEAAAA&_nc_gid=nEef1bI_JkA45svERuZO-g'
        .'&_nc_tpa=Q5bMBQJfFjPYMkHGddVrM4hAzArEVaEdjgxrw0JRiPYmx0m7MJN6H9j-pNgrTpoHyaXFsSv-IwYpOFDz0g'
        .'&oh=00_AQI9ezYX7tg6RwJTw1CZ4CCf8NFC6eNjm_iIexKtXbijlw&oe=6AA46A69';

    expect(strlen($avatarUrl))->toBeGreaterThan(255);

    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
        'avatar_url' => $avatarUrl,
    ]);

    expect($account->fresh()->avatar_url)->toBe($avatarUrl);
});
