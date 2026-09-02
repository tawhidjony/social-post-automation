<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PostMediaService
{
    public function disk(): string
    {
        return (string) config('post.media_disk');
    }

    /**
     * @return list<string>
     */
    public function storeUploadedFiles(Request $request): array
    {
        if (! $request->hasFile('media')) {
            return [];
        }

        if ($defaultUrl = $this->defaultMediaUrl()) {
            return [$defaultUrl];
        }

        $disk = Storage::disk($this->disk());
        $urls = [];

        foreach ($request->file('media') as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('posts_media', $this->disk());
            $urls[] = $disk->url($path);
        }

        return $urls;
    }

    public function defaultMediaPath(): ?string
    {
        $path = config('post.default_media_path');

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function defaultMediaUrl(): ?string
    {
        $path = $this->defaultMediaPath();

        if ($path === null) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @return array{contents: string, filename: string}|null
     */
    public function resolvePublishMedia(Post $post): ?array
    {
        if (empty($post->media)) {
            return null;
        }

        if ($path = $this->defaultMediaPath()) {
            $disk = Storage::disk('public');

            if ($disk->exists($path)) {
                return [
                    'contents' => $disk->get($path),
                    'filename' => basename($path),
                ];
            }
        }

        $mediaUrl = $post->media[0];
        $contents = $this->getContents($mediaUrl);

        if ($contents === null) {
            return null;
        }

        return [
            'contents' => $contents,
            'filename' => $this->filenameFromUrl($mediaUrl),
        ];
    }

    /**
     * @param  list<string>  $previousMedia
     * @param  list<string>  $retainedMedia
     */
    public function deleteRemoved(array $previousMedia, array $retainedMedia): void
    {
        $removed = array_diff($previousMedia, $retainedMedia);

        foreach ($removed as $url) {
            $path = $this->pathFromUrl($url);

            if ($path !== null) {
                Storage::disk($this->disk())->delete($path);
            }
        }
    }

    public function pathFromUrl(string $url): ?string
    {
        foreach ($this->urlPrefixes() as $prefix) {
            if (str_starts_with($url, $prefix)) {
                $path = ltrim(substr($url, strlen($prefix)), '/');

                return $path !== '' ? $path : null;
            }
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return null;
        }

        $relativePath = substr($path, strlen('/storage/'));

        return $relativePath !== '' ? $relativePath : null;
    }

    public function getContents(string $url): ?string
    {
        $path = $this->pathFromUrl($url);

        if ($path === null) {
            return null;
        }

        foreach ($this->readableDisks() as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($path)) {
                return $disk->get($path);
            }
        }

        return null;
    }

    public function filenameFromUrl(string $url): string
    {
        $path = $this->pathFromUrl($url);

        if ($path === null) {
            return 'image.jpg';
        }

        return basename($path) ?: 'image.jpg';
    }

    /**
     * @return list<string>
     */
    private function readableDisks(): array
    {
        $disks = [$this->disk()];

        if (! in_array('public', $disks, true)) {
            $disks[] = 'public';
        }

        return $disks;
    }

    /**
     * @return list<string>
     */
    private function urlPrefixes(): array
    {
        $prefixes = [];

        $publicBaseUrl = config('filesystems.disks.public.url');

        if (is_string($publicBaseUrl) && $publicBaseUrl !== '') {
            $prefixes[] = rtrim($publicBaseUrl, '/').'/';
        }

        $s3BaseUrl = config('filesystems.disks.s3.url');

        if (is_string($s3BaseUrl) && $s3BaseUrl !== '') {
            $prefixes[] = rtrim($s3BaseUrl, '/').'/';
        }

        return array_values(array_unique($prefixes));
    }
}
