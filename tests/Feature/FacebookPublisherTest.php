<?php

use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\Publishers\FacebookPublisher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('facebook publisher uploads photo bytes instead of a remote url', function () {
    Storage::fake('public');
    Storage::fake('s3');

    Storage::disk('public')->put('posts_media/default-placeholder.png', 'fake-image-content');

    config([
        'post.media_disk' => 's3',
        'post.default_media_path' => 'posts_media/default-placeholder.png',
        'filesystems.disks.s3.url' => 'http://localhost:9001/post-automation',
    ]);

    Storage::disk('s3')->put('posts_media/photo.jpg', 'fake-image-content');

    Http::fake([
        'graph.facebook.com/*' => Http::response(['id' => 'fb-photo-id'], 200),
    ]);

    $post = Post::factory()->create([
        'media' => ['http://localhost:9001/post-automation/posts_media/photo.jpg'],
        'content' => 'Caption text',
    ]);

    $account = SocialAccount::factory()->create([
        'provider' => 'facebook',
        'provider_account_id' => '123456789',
    ]);

    $result = app(FacebookPublisher::class)->publish($post, $account);

    expect($result)->toBe([
        'success' => true,
        'id' => 'fb-photo-id',
    ]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/photos')
            && $request->method() === 'POST';
    });
});

test('facebook publisher returns an error when photo media cannot be loaded', function () {
    Http::fake();

    config(['post.default_media_path' => null]);

    $post = Post::factory()->create([
        'media' => ['http://localhost:9001/post-automation/posts_media/missing.jpg'],
        'content' => 'Caption text',
    ]);

    $account = SocialAccount::factory()->create([
        'provider' => 'facebook',
        'provider_account_id' => '123456789',
    ]);

    $result = app(FacebookPublisher::class)->publish($post, $account);

    expect($result)->toBe([
        'success' => false,
        'error' => 'Post media file could not be loaded.',
    ]);

    Http::assertNothingSent();
});

test('facebook publisher posts text-only feed updates without media', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['id' => 'fb-post-id'], 200),
    ]);

    $post = Post::factory()->create([
        'media' => null,
        'content' => 'Hello Facebook',
    ]);

    $account = SocialAccount::factory()->create([
        'provider' => 'facebook',
        'provider_account_id' => '123456789',
    ]);

    $result = app(FacebookPublisher::class)->publish($post, $account);

    expect($result)->toBe([
        'success' => true,
        'id' => 'fb-post-id',
    ]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/feed')
            && $request->method() === 'POST';
    });
});
