<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\PostMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(private PostMediaService $postMedia) {}

    /**
     * @var list<string>
     */
    private const EditableStatuses = ['draft', 'scheduled'];

    public function index(Request $request): Response
    {
        $workspaceId = $request->user()->ensureCurrentWorkspace()->id;

        $posts = Post::query()
            ->where('workspace_id', $workspaceId)
            ->with(['targets.socialAccount:id,provider,name,avatar_url'])
            ->latest()
            ->get();

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Posts/Create', [
            'socialAccounts' => $this->activeSocialAccounts(
                $request->user()->ensureCurrentWorkspace()->id
            ),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $workspaceId = $request->user()->ensureCurrentWorkspace()->id;

        $post = Post::query()->create([
            'workspace_id' => $workspaceId,
            'user_id' => $request->user()->id,
            'content' => $validated['content'] ?? '',
            'media' => $this->postMedia->storeUploadedFiles($request),
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'scheduled',
        ]);

        $this->syncTargets($post, $validated['social_account_ids']);

        return redirect()->route('posts.index')->with('success', 'Post scheduled successfully.');
    }

    public function show(Request $request, Post $post): Response
    {
        $this->ensurePostBelongsToWorkspace(
            $post,
            $request->user()->ensureCurrentWorkspace()->id
        );

        $post->load(['targets.socialAccount:id,provider,name,avatar_url']);

        return Inertia::render('Posts/Show', [
            'post' => $post,
            'editable' => $this->isEditable($post),
        ]);
    }

    public function edit(Request $request, Post $post): Response
    {
        $workspaceId = $request->user()->ensureCurrentWorkspace()->id;

        $this->ensurePostBelongsToWorkspace($post, $workspaceId);
        $this->ensurePostIsEditable($post);

        $post->load(['targets:id,post_id,social_account_id']);

        return Inertia::render('Posts/Edit', [
            'post' => $post,
            'socialAccounts' => $this->activeSocialAccounts($workspaceId),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $workspaceId = $request->user()->ensureCurrentWorkspace()->id;

        $this->ensurePostBelongsToWorkspace($post, $workspaceId);
        $this->ensurePostIsEditable($post);

        $validated = $request->validated();
        $existingMedia = $validated['existing_media'] ?? [];
        $newMedia = $this->postMedia->storeUploadedFiles($request);
        $media = array_values(array_merge($existingMedia, $newMedia));

        $this->postMedia->deleteRemoved($post->media ?? [], $existingMedia);

        $post->update([
            'content' => $validated['content'] ?? '',
            'media' => $media,
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'scheduled',
        ]);

        $this->syncTargets($post, $validated['social_account_ids']);

        return redirect()->route('posts.index')->with('success', 'Post updated successfully.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->ensurePostBelongsToWorkspace(
            $post,
            $request->user()->ensureCurrentWorkspace()->id
        );
        $this->ensurePostIsEditable($post);

        $this->postMedia->deleteRemoved($post->media ?? [], []);

        $post->delete();

        return redirect()->route('posts.index')->with('success', 'Post deleted successfully.');
    }

    private function ensurePostBelongsToWorkspace(Post $post, int $workspaceId): void
    {
        abort_unless($post->workspace_id === $workspaceId, 404);
    }

    private function ensurePostIsEditable(Post $post): void
    {
        abort_unless($this->isEditable($post), 403);
    }

    private function isEditable(Post $post): bool
    {
        return in_array($post->status, self::EditableStatuses, true);
    }

    /**
     * @return Collection<int, SocialAccount>
     */
    private function activeSocialAccounts(int $workspaceId)
    {
        return SocialAccount::query()
            ->where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->get(['id', 'provider', 'name', 'avatar_url']);
    }

    /**
     * @param  list<int>  $socialAccountIds
     */
    private function syncTargets(Post $post, array $socialAccountIds): void
    {
        $post->targets()->delete();

        foreach ($socialAccountIds as $accountId) {
            $post->targets()->create([
                'social_account_id' => $accountId,
                'status' => 'pending',
            ]);
        }
    }
}
