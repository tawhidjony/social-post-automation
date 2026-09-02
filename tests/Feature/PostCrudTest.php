<?php

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from posts index', function () {
    $this->get(route('posts.index'))->assertRedirect(route('login'));
});

test('authenticated users can store a scheduled post', function () {
    Storage::fake('public');

    Storage::disk('public')->put('posts_media/default-placeholder.png', 'placeholder');

    config(['post.default_media_path' => 'posts_media/default-placeholder.png']);

    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();
    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $response = $this->actingAs($user)->post(route('posts.store'), [
        'social_account_ids' => [$account->id],
        'content' => 'Hello scheduled world',
        'scheduled_at' => now()->addDay()->toDateTimeString(),
        'media' => [UploadedFile::fake()->image('photo.jpg')],
    ]);

    $response->assertRedirect(route('posts.index'));

    $post = Post::query()->first();

    expect($post)->not->toBeNull()
        ->and($post->workspace_id)->toBe($workspace->id)
        ->and($post->user_id)->toBe($user->id)
        ->and($post->content)->toBe('Hello scheduled world')
        ->and($post->status)->toBe('scheduled')
        ->and($post->targets)->toHaveCount(1)
        ->and($post->targets->first()->social_account_id)->toBe($account->id)
        ->and($post->media)->toHaveCount(1);
});

test('index only lists posts for the current workspace', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();

    $ownPost = Post::factory()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
        'content' => 'Mine',
    ]);

    $otherUser = User::factory()->create();
    $otherWorkspace = $otherUser->ensureCurrentWorkspace();

    Post::factory()->create([
        'user_id' => $otherUser->id,
        'workspace_id' => $otherWorkspace->id,
        'content' => 'Theirs',
    ]);

    $this->actingAs($user)
        ->get(route('posts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Posts/Index')
            ->has('posts', 1)
            ->where('posts.0.id', $ownPost->id)
            ->where('posts.0.content', 'Mine')
        );
});

test('show update and destroy of another workspace post return not found', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();
    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $otherUser = User::factory()->create();
    $otherWorkspace = $otherUser->ensureCurrentWorkspace();
    $foreignPost = Post::factory()->create([
        'user_id' => $otherUser->id,
        'workspace_id' => $otherWorkspace->id,
    ]);

    $this->actingAs($user)
        ->get(route('posts.show', $foreignPost))
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('posts.update', $foreignPost), [
            'social_account_ids' => [$account->id],
            'content' => 'Hacked',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('posts.destroy', $foreignPost))
        ->assertNotFound();
});

test('update and destroy are forbidden for processing and published posts', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();
    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $processing = Post::factory()->processing()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $published = Post::factory()->published()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    foreach ([$processing, $published] as $post) {
        $this->actingAs($user)
            ->put(route('posts.update', $post), [
                'social_account_ids' => [$account->id],
                'content' => 'Nope',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('posts.destroy', $post))
            ->assertForbidden();
    }
});

test('store validation fails without accounts or content and media', function () {
    $user = User::factory()->create();
    $user->ensureCurrentWorkspace();

    $this->actingAs($user)
        ->from(route('posts.create'))
        ->post(route('posts.store'), [
            'social_account_ids' => [],
            'content' => '',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect(route('posts.create'))
        ->assertSessionHasErrors(['social_account_ids', 'content']);
});

test('authenticated users can update a scheduled post', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();
    $account = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
    ]);
    $otherAccount = SocialAccount::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $post = Post::factory()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
        'content' => 'Original',
        'status' => 'scheduled',
    ]);

    $post->targets()->create([
        'social_account_id' => $account->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->put(route('posts.update', $post), [
        'social_account_ids' => [$otherAccount->id],
        'content' => 'Updated content',
        'existing_media' => [],
        'scheduled_at' => now()->addDays(2)->toDateTimeString(),
    ]);

    $response->assertRedirect(route('posts.index'));

    $post->refresh();

    expect($post->content)->toBe('Updated content')
        ->and($post->targets)->toHaveCount(1)
        ->and($post->targets->first()->social_account_id)->toBe($otherAccount->id);
});

test('authenticated users can delete a scheduled post', function () {
    $user = User::factory()->create();
    $workspace = $user->ensureCurrentWorkspace();

    $post = Post::factory()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($user)
        ->delete(route('posts.destroy', $post))
        ->assertRedirect(route('posts.index'));

    expect(Post::query()->find($post->id))->toBeNull();
});
