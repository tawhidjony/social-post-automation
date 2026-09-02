<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\Publishers\FacebookPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Post $post) {}

    public function handle(): void
    {
        $hasFailed = false;
        $hasPublished = false;

        foreach ($this->post->targets as $target) {
            if ($target->status === 'published') {
                $hasPublished = true;

                continue;
            }

            $account = $target->socialAccount;

            if (! $account || ! $account->is_active) {
                $target->update([
                    'status' => 'failed',
                    'error_message' => 'Social account is inactive or missing.',
                ]);
                $hasFailed = true;

                continue;
            }

            $result = match ($account->provider) {
                'facebook' => app(FacebookPublisher::class)->publish($this->post, $account),
                default => ['success' => false, 'error' => 'Unsupported platform publisher.'],
            };

            if ($result['success']) {
                $target->update([
                    'status' => 'published',
                    'platform_post_id' => $result['id'],
                    'published_at' => now(),
                ]);
                $hasPublished = true;
            } else {
                $target->update([
                    'status' => 'failed',
                    'error_message' => $result['error'],
                ]);
                $hasFailed = true;
            }
        }

        $finalStatus = match (true) {
            $hasPublished && ! $hasFailed => 'published',
            $hasPublished && $hasFailed => 'partially_failed',
            default => 'failed',
        };

        $this->post->update(['status' => $finalStatus]);
    }
}
