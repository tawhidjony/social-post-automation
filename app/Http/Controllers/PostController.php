<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function create(Request $request): Response
    {
        $workspaceId = $request->user()->current_workspace_id;

        $socialAccounts = SocialAccount::where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->get(['id', 'provider', 'name', 'avatar_url']);

        return Inertia::render('Posts/Create', [
            'socialAccounts' => $socialAccounts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'social_account_ids' => 'required|array|min:1',
            'social_account_ids.*' => 'exists:social_accounts,id',
            'content' => 'required_without:media|nullable|string',
            'media' => 'nullable|array',
            'media.*' => 'image|max:10240', // 10MB Limit
            'scheduled_at' => 'required|date|after:now',
        ]);

        $workspaceId = $request->user()->current_workspace_id;

        // Upload media files
        $mediaUrls = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store('posts_media', 'public');
                $mediaUrls[] = asset('storage/' . $path);
            }
        }

        $post = Post::create([
            'workspace_id' => $workspaceId,
            'user_id' => $request->user()->id,
            'content' => $validated['content'] ?? '',
            'media' => $mediaUrls,
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'scheduled',
        ]);

        foreach ($validated['social_account_ids'] as $accountId) {
            $post->targets()->create([
                'social_account_id' => $accountId,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('posts.create')->with('success', 'Post Scheduled Successfully!');
    }
}