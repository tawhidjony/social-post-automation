<?php

use App\Models\Post;
use App\Services\PostMediaService;
use Illuminate\Support\Facades\Storage;

test('path from url resolves public disk urls', function () {
    Storage::fake('public');

    config([
        'post.media_disk' => 'public',
        'filesystems.disks.public.url' => 'http://localhost/storage',
    ]);

    $service = new PostMediaService;

    expect($service->pathFromUrl('http://localhost/storage/posts_media/photo.jpg'))
        ->toBe('posts_media/photo.jpg');
});

test('path from url resolves legacy storage symlink urls', function () {
    config(['post.media_disk' => 'public']);

    $service = new PostMediaService;

    expect($service->pathFromUrl('http://localhost:8000/storage/posts_media/photo.jpg'))
        ->toBe('posts_media/photo.jpg');
});

test('filename from url returns basename', function () {
    config(['post.media_disk' => 'public']);

    $service = new PostMediaService;

    expect($service->filenameFromUrl('http://localhost:8000/storage/posts_media/photo.jpg'))
        ->toBe('photo.jpg');
});

test('resolve publish media uses configured default placeholder when post has media', function () {
    Storage::fake('public');

    Storage::disk('public')->put('posts_media/default-placeholder.png', 'placeholder-image');

    config(['post.default_media_path' => 'posts_media/default-placeholder.png']);

    $service = new PostMediaService;

    $post = new Post([
        'media' => ['http://localhost:9001/post-automation/posts_media/other.jpg'],
    ]);

    expect($service->resolvePublishMedia($post))->toBe([
        'contents' => 'placeholder-image',
        'filename' => 'default-placeholder.png',
    ]);
});

test('get contents reads file from configured disk', function () {
    Storage::fake('s3');

    config([
        'post.media_disk' => 's3',
        'filesystems.disks.s3.url' => 'http://localhost:9001/post-automation',
    ]);

    Storage::disk('s3')->put('posts_media/photo.jpg', 'image-bytes');

    $service = new PostMediaService;

    expect($service->getContents('http://localhost:9001/post-automation/posts_media/photo.jpg'))
        ->toBe('image-bytes');
});
