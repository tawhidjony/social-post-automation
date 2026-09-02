<?php

use App\Jobs\PublishSocialPostJob;
use App\Models\Post;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $duePosts = Post::where('status', 'scheduled')
        ->where('scheduled_at', '<=', now())
        ->get();

    foreach ($duePosts as $post) {
        $post->update(['status' => 'processing']);
        PublishSocialPostJob::dispatch($post);
    }
})->name('publish-due-social-posts')->everyMinute()->withoutOverlapping();
