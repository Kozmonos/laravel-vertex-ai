<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Support;

use Kozmonos\VertexAi\Contracts\ReferenceImageLoader;
use Kozmonos\VertexAi\Exceptions\AiProviderException;
use Kozmonos\VertexAi\Support\HttpReferenceImageLoader;
use Illuminate\Support\Facades\Storage;

final class LaravelS3ReferenceImageLoader implements ReferenceImageLoader
{
    public function __construct(
        private readonly HttpReferenceImageLoader $http,
    ) {}

    /**
     * @return array{mime_type: string, bytes: string}
     */
    public function read(string $url): array
    {
        if (str_starts_with($url, 'data:')) {
            return $this->http->read($url);
        }

        $disk = Storage::disk('s3');
        $configuredUrl = config('filesystems.disks.s3.url');
        $baseUrl = is_string($configuredUrl) ? rtrim($configuredUrl, '/') : '';

        if ($baseUrl !== '' && str_starts_with($url, $baseUrl.'/')) {
            $key = ltrim(substr($url, strlen($baseUrl)), '/');

            if ($disk->exists($key)) {
                $contents = $disk->get($key);

                if (is_string($contents) && $contents !== '') {
                    return [
                        'mime_type' => $this->guessMimeType($contents, $url),
                        'bytes' => $contents,
                    ];
                }
            }
        }

        try {
            return $this->http->read($url);
        } catch (AiProviderException $exception) {
            throw $exception;
        }
    }

    private function guessMimeType(string $contents, string $url): string
    {
        if (str_starts_with($contents, "\x89PNG")) {
            return 'image/png';
        }

        if (str_starts_with($contents, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_contains(mb_strtolower($url), '.jpg') || str_contains(mb_strtolower($url), '.jpeg')) {
            return 'image/jpeg';
        }

        return 'image/png';
    }
}
