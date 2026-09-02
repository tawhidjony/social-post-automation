<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Post Media Disk
    |--------------------------------------------------------------------------
    |
    | Disk used for storing post media. Use "s3" (or another public cloud disk)
    | in production so Facebook and other platforms can fetch image URLs.
    |
    */

    'media_disk' => env('MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),

    /*
    |--------------------------------------------------------------------------
    | Processing Timeout
    |--------------------------------------------------------------------------
    |
    | Minutes a post may remain in "processing" before the scheduler retries
    | dispatching its publish job (when all targets are still pending).
    |
    */

    'processing_timeout_minutes' => (int) env('POST_PROCESSING_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Default Post Media
    |--------------------------------------------------------------------------
    |
    | Temporary placeholder image used for every post that includes media.
    | Set DEFAULT_POST_MEDIA_PATH=null to use uploaded files instead.
    |
    */

    'default_media_path' => env('DEFAULT_POST_MEDIA_PATH', 'posts_media/default-placeholder.png'),

];
