<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Batch\Support;

use Kozmonos\VertexAi\Contracts\ReferenceImageLoader;
use Kozmonos\VertexAi\Vertex\VertexBatchService;
use Kozmonos\VertexAi\Support\ConfigValue;
use Illuminate\Support\Facades\Cache;

/**
 * Copies reference images from object storage or HTTP into the Vertex batch GCS bucket for JSONL fileData URIs.
 */
final class BatchReferenceImageStager
{
    private const SHARED_PREFIX = 'batch-refs/shared';

    /** @var array<string, string> */
    private array $stagedByUrl = [];

    public function __construct(
        private readonly VertexBatchService $batchService,
        private readonly ReferenceImageLoader $referenceImages,
        private readonly string $stagingPrefix = self::SHARED_PREFIX,
    ) {}

    public function stage(string $sourceUrl): string
    {
        if (isset($this->stagedByUrl[$sourceUrl])) {
            return $this->stagedByUrl[$sourceUrl];
        }

        $normalizedUrl = $this->normalizeSourceUrl($sourceUrl);

        if (isset($this->stagedByUrl[$normalizedUrl])) {
            return $this->stagedByUrl[$sourceUrl] = $this->stagedByUrl[$normalizedUrl];
        }

        $cachedGsUri = $this->rememberedGsUri($this->urlCacheKey($normalizedUrl));

        if ($cachedGsUri !== null) {
            return $this->rememberUrl($sourceUrl, $normalizedUrl, $cachedGsUri);
        }

        $image = $this->referenceImages->read($sourceUrl);
        $binary = $image['bytes'];

        if ($binary === '') {
            throw new \InvalidArgumentException('Reference image bytes could not be loaded for batch staging.');
        }

        $contentHash = hash('sha256', $binary);
        $cachedGsUri = $this->rememberedGsUri($this->contentCacheKey($contentHash));

        if ($cachedGsUri !== null) {
            $this->rememberUrlMapping($normalizedUrl, $cachedGsUri);

            return $this->rememberUrl($sourceUrl, $normalizedUrl, $cachedGsUri);
        }

        $extension = match ($image['mime_type']) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };

        $objectName = rtrim($this->stagingPrefix, '/')."/{$contentHash}.{$extension}";
        $gsUri = $this->batchService->uploadBinary($objectName, $binary, $image['mime_type']);

        $this->rememberUrlMapping($normalizedUrl, $gsUri);
        $this->rememberContentMapping($contentHash, $gsUri);

        return $this->rememberUrl($sourceUrl, $normalizedUrl, $gsUri);
    }

    /**
     * @param  list<string>  $sourceUrls
     */
    public function prefetch(array $sourceUrls): void
    {
        foreach ($this->uniqueUrls($sourceUrls) as $sourceUrl) {
            $this->stage($sourceUrl);
        }
    }

    /**
     * @param  list<string>  $sourceUrls
     * @return list<array{fileUri: string, mimeType: string}>
     */
    public function stageAll(array $sourceUrls): array
    {
        $staged = [];

        foreach ($this->uniqueUrls($sourceUrls) as $sourceUrl) {
            $gsUri = $this->stage($sourceUrl);
            $staged[] = [
                'fileUri' => $gsUri,
                'mimeType' => $this->mimeTypeFromGsUri($gsUri),
            ];
        }

        return $staged;
    }

    public static function prefixForBatch(): string
    {
        return self::SHARED_PREFIX;
    }

    /**
     * @param  list<string>  $sourceUrls
     * @return list<string>
     */
    private function uniqueUrls(array $sourceUrls): array
    {
        $unique = [];

        foreach ($sourceUrls as $sourceUrl) {
            if ($sourceUrl === '') {
                continue;
            }

            $unique[$sourceUrl] = $sourceUrl;
        }

        return array_values($unique);
    }

    private function normalizeSourceUrl(string $sourceUrl): string
    {
        $withoutQuery = strtok($sourceUrl, '?') ?: $sourceUrl;
        $configuredUrl = config('filesystems.disks.s3.url');
        $baseUrl = is_string($configuredUrl) ? rtrim($configuredUrl, '/') : '';

        if ($baseUrl !== '' && str_starts_with($withoutQuery, $baseUrl.'/')) {
            return ltrim(substr($withoutQuery, strlen($baseUrl)), '/');
        }

        return $withoutQuery;
    }

    private function rememberUrl(string $sourceUrl, string $normalizedUrl, string $gsUri): string
    {
        $this->stagedByUrl[$sourceUrl] = $gsUri;
        $this->stagedByUrl[$normalizedUrl] = $gsUri;

        return $gsUri;
    }

    private function rememberedGsUri(string $cacheKey): ?string
    {
        $cached = Cache::get($cacheKey);

        return is_string($cached) && $cached !== '' ? $cached : null;
    }

    private function rememberUrlMapping(string $normalizedUrl, string $gsUri): void
    {
        Cache::put($this->urlCacheKey($normalizedUrl), $gsUri, $this->cacheTtlSeconds());
    }

    private function rememberContentMapping(string $contentHash, string $gsUri): void
    {
        Cache::put($this->contentCacheKey($contentHash), $gsUri, $this->cacheTtlSeconds());
    }

    private function urlCacheKey(string $normalizedUrl): string
    {
        return 'vertex_batch_ref:url:'.hash('sha256', $normalizedUrl);
    }

    private function contentCacheKey(string $contentHash): string
    {
        return 'vertex_batch_ref:content:'.$contentHash;
    }

    private function cacheTtlSeconds(): int
    {
        $ttl = ConfigValue::int(config('vertex-ai.vertex.batch.reference_cache_ttl'), 604800);

        return $ttl > 0 ? $ttl : 604800;
    }

    private function mimeTypeFromGsUri(string $gsUri): string
    {
        $lower = mb_strtolower($gsUri);

        return match (true) {
            str_ends_with($lower, '.jpg'), str_ends_with($lower, '.jpeg') => 'image/jpeg',
            str_ends_with($lower, '.webp') => 'image/webp',
            default => 'image/png',
        };
    }
}
