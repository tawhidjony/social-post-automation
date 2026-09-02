<?php

namespace App\Services\Publishers;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\PostMediaService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FacebookPublisher implements SocialPublisherInterface
{
    public function __construct(private PostMediaService $postMedia) {}

    public function publish(Post $post, SocialAccount $account): array
    {
        $accessToken = $account->access_token;

        if (! empty($post->media)) {
            return $this->publishPhoto($post, $account, $accessToken);
        }

        return $this->publishFeedPost($post, $account, $accessToken);
    }

    private function publishPhoto(Post $post, SocialAccount $account, string $accessToken): array
    {
        $media = $this->postMedia->resolvePublishMedia($post);

        if ($media === null) {
            return [
                'success' => false,
                'error' => 'Post media file could not be loaded.',
            ];
        }

        $endpoint = "https://graph.facebook.com/v19.0/{$account->provider_account_id}/photos";

        $response = Http::attach(
            'source',
            $media['contents'],
            $media['filename'],
        )->post($endpoint, [
            'caption' => $post->content,
            'access_token' => $accessToken,
        ]);

        return $this->responseResult($response);
    }

    private function publishFeedPost(Post $post, SocialAccount $account, string $accessToken): array
    {
        $endpoint = "https://graph.facebook.com/v19.0/{$account->provider_account_id}/feed";

        $response = Http::post($endpoint, [
            'message' => $post->content,
            'access_token' => $accessToken,
        ]);

        return $this->responseResult($response);
    }

    /**
     * @return array{success: bool, id?: string, error?: string}
     */
    private function responseResult(Response $response): array
    {
        if ($response->successful()) {
            return [
                'success' => true,
                'id' => $response->json('id'),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'Facebook Graph API publish failed.'),
        ];
    }
}
