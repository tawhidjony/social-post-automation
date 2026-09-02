<?php

namespace App\Console\Commands;

use App\Jobs\PublishSocialPostJob;
use App\Models\Post;
use App\Models\PostTarget;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class PublishDueSocialPostsCommand extends Command
{
    protected $signature = 'posts:publish-due';

    protected $description = 'Dispatch publish jobs for due scheduled posts and recover stuck processing posts';

    public function handle(): int
    {
        $this->dispatchDueScheduledPosts();
        $this->recoverStuckProcessingPosts();
        $this->reconcileProcessingPosts();

        return self::SUCCESS;
    }

    private function dispatchDueScheduledPosts(): void
    {
        Post::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->eachById(function (Post $post): void {
                $post->update(['status' => 'processing']);
                PublishSocialPostJob::dispatch($post);
            });
    }

    private function recoverStuckProcessingPosts(): void
    {
        $timeoutMinutes = (int) config('post.processing_timeout_minutes');

        Post::query()
            ->where('status', 'processing')
            ->where('updated_at', '<=', now()->subMinutes($timeoutMinutes))
            ->with('targets')
            ->eachById(function (Post $post): void {
                if (! $this->allTargetsArePending($post)) {
                    return;
                }

                PublishSocialPostJob::dispatch($post);
                $post->touch();
            });
    }

    private function reconcileProcessingPosts(): void
    {
        Post::query()
            ->where('status', 'processing')
            ->with('targets')
            ->eachById(function (Post $post): void {
                if ($post->targets->isEmpty()) {
                    return;
                }

                if ($post->targets->contains(fn ($target) => $target->status === 'pending')) {
                    return;
                }

                $post->update(['status' => $this->resolveFinalStatus($post->targets)]);
            });
    }

    /**
     * @param  Collection<int, PostTarget>  $targets
     */
    private function resolveFinalStatus(Collection $targets): string
    {
        $hasPublished = $targets->contains(fn ($target) => $target->status === 'published');
        $hasFailed = $targets->contains(fn ($target) => $target->status === 'failed');

        return match (true) {
            $hasPublished && ! $hasFailed => 'published',
            $hasPublished && $hasFailed => 'partially_failed',
            default => 'failed',
        };
    }

    private function allTargetsArePending(Post $post): bool
    {
        if ($post->targets->isEmpty()) {
            return false;
        }

        return $post->targets->every(fn ($target) => $target->status === 'pending');
    }
}
