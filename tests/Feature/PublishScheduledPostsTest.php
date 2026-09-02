<?php

use App\Jobs\PublishSocialPostJob;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

test('posts publish due command dispatches jobs for due scheduled posts', function () {
    Queue::fake([PublishSocialPostJob::class]);

    $duePost = Post::factory()->create([
        'scheduled_at' => now()->subMinute(),
        'status' => 'scheduled',
    ]);

    $duePost->targets()->create([
        'social_account_id' => SocialAccount::factory()->create([
            'workspace_id' => $duePost->workspace_id,
        ])->id,
        'status' => 'pending',
    ]);

    $futurePost = Post::factory()->create([
        'scheduled_at' => now()->addHour(),
        'status' => 'scheduled',
    ]);

    Artisan::call('posts:publish-due');

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

test('schedule is registered to publish due posts every minute', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) ($event->command ?? ''), 'posts:publish-due'));

    expect($event)->not->toBeNull();
});

test('posts publish due command redispatches stuck processing posts with pending targets', function () {
    Queue::fake([PublishSocialPostJob::class]);

    config(['post.processing_timeout_minutes' => 10]);

    $stuckPost = Post::factory()->processing()->create([
        'updated_at' => now()->subMinutes(15),
    ]);

    $stuckPost->targets()->create([
        'social_account_id' => SocialAccount::factory()->create([
            'workspace_id' => $stuckPost->workspace_id,
        ])->id,
        'status' => 'pending',
    ]);

    Artisan::call('posts:publish-due');

    Queue::assertPushed(PublishSocialPostJob::class, function (PublishSocialPostJob $job) use ($stuckPost) {
        return $job->post->is($stuckPost);
    });
});

test('posts publish due command reconciles processing posts when all targets are finished', function () {
    Queue::fake([PublishSocialPostJob::class]);

    $post = Post::factory()->processing()->create();

    $post->targets()->create([
        'social_account_id' => SocialAccount::factory()->create([
            'workspace_id' => $post->workspace_id,
        ])->id,
        'status' => 'published',
    ]);

    Artisan::call('posts:publish-due');

    expect($post->fresh()->status)->toBe('published');

    Queue::assertNothingPushed();
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
