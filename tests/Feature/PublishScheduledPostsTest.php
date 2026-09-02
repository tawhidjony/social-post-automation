<?php

use App\Jobs\PublishSocialPostJob;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

test('schedule run dispatches jobs for due scheduled posts', function () {
    Queue::fake([PublishSocialPostJob::class]);

    $duePost = Post::factory()->create([
        'scheduled_at' => now()->subMinute(),
        'status' => 'scheduled',
    ]);

    $futurePost = Post::factory()->create([
        'scheduled_at' => now()->addHour(),
        'status' => 'scheduled',
    ]);

    Artisan::call('schedule:run');

    $duePost->refresh();
    $futurePost->refresh();

    expect($duePost->status)->toBe('processing')
        ->and($futurePost->status)->toBe('scheduled');

    Queue::assertPushed(PublishSocialPostJob::class, function (PublishSocialPostJob $job) use ($duePost) {
        return $job->post->is($duePost);
    });

    Queue::assertNotPushed(PublishSocialPostJob::class, function (PublishSocialPostJob $job) use ($futurePost) {
        return $job->post->is($futurePost);
    });
});

test('authenticated users can store a post with an iso8601 scheduled_at', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();
    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $scheduledAt = now()->addHour()->toIso8601String();

    $response = $this->actingAs($user)->post(route('posts.store'), [
        'social_account_ids' => [$account->id],
        'content' => 'ISO scheduled post',
        'scheduled_at' => $scheduledAt,
    ]);

    $response->assertRedirect(route('posts.index'));

    $post = Post::query()->first();

    expect($post)->not->toBeNull()
        ->and($post->status)->toBe('scheduled')
        ->and($post->scheduled_at->equalTo($scheduledAt))->toBeTrue();
});
