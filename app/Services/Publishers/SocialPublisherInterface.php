<?php

namespace App\Services\Publishers;

use App\Models\Post;
use App\Models\SocialAccount;

interface SocialPublisherInterface
{
    public function publish(Post $post, SocialAccount $account): array;
}