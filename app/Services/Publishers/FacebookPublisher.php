<?php

namespace App\Services\Publishers;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;

class FacebookPublisher implements SocialPublisherInterface
{
    public function publish(Post $post, SocialAccount $account): array
    {
        $accessToken = $account->access_token; // Decrypted via Model Cast
        $endpoint = "https://graph.facebook.com/v19.0/{$account->provider_account_id}/feed";

        $payload = [
            'message' => $post->content,
            'access_token' => $accessToken,
        ];

        // Attach image if present
        if (!empty($post->media)) {
            $endpoint = "https://graph.facebook.com/v19.0/{$account->provider_account_id}/photos";
            $payload['url'] = $post->media[0];
            $payload['caption'] = $post->content;
        }

        $response = Http::post($endpoint, $payload);

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