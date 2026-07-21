<?php

declare(strict_types=1);

use Kozmonos\LaravelVertexAi\Batch\Support\BatchReferenceImageStager;
use Kozmonos\LaravelVertexAi\Tests\Support\TestVertexBatchJob;
use Kozmonos\LaravelVertexAi\Tests\TestCase;
use Kozmonos\VertexAi\Contracts\ReferenceImageLoader;
use Kozmonos\VertexAi\Infrastructure\Batch\FakeBatchJobGateway;
use Kozmonos\VertexAi\Infrastructure\Gcs\InMemoryGcsObjectStore;
use Kozmonos\VertexAi\Vertex\VertexBatchService;
use Illuminate\Support\Facades\Cache;

uses(TestCase::class);

function makeBatchReferenceImageStager(string $prefix = 'batch-refs/test-prefix'): BatchReferenceImageStager
{
    $batchService = new VertexBatchService(
        makeTestVertexConfig(),
        new InMemoryGcsObjectStore('test-batch-bucket'),
        new FakeBatchJobGateway(),
    );

    return new BatchReferenceImageStager(
        $batchService,
        new class implements ReferenceImageLoader
        {
            public function read(string $url): array
            {
                return [
                    'bytes' => 'png-bytes',
                    'mime_type' => 'image/png',
                ];
            }
        },
        $prefix,
    );
}

it('stages reference images to gcs with deduplicated gs uris', function () {
    config([
        'vertex-ai.vertex.batch.gcs_bucket' => 'test-batch-bucket',
        'vertex-ai.vertex.batch.reference_cache_ttl' => 604800,
    ]);

    $stager = makeBatchReferenceImageStager();
    $sourceUrl = 'https://s3.example.test/assets/projects/1/refs/character.png';

    $first = $stager->stage($sourceUrl);
    $second = $stager->stage($sourceUrl);

    expect($first)->toStartWith('gs://test-batch-bucket/batch-refs/test-prefix/')
        ->and($second)->toBe($first);
});

it('reuses cached gcs uris across stager instances without re-uploading', function () {
    Cache::flush();
    config([
        'vertex-ai.vertex.batch.gcs_bucket' => 'test-batch-bucket',
        'vertex-ai.vertex.batch.reference_cache_ttl' => 604800,
    ]);

    $sourceUrl = 'https://s3.example.test/assets/projects/1/refs/character.png';

    $first = makeBatchReferenceImageStager('batch-refs/test-prefix')->stage($sourceUrl);
    $second = makeBatchReferenceImageStager('batch-refs/another-prefix')->stage($sourceUrl);

    expect($second)->toBe($first);
});

it('builds gemini image batch requests with staged fileData references', function () {
    config([
        'vertex-ai.vertex.batch.gcs_bucket' => 'test-batch-bucket',
        'vertex-ai.vertex.batch.reference_cache_ttl' => 604800,
    ]);

    $sourceUrl = 'https://s3.example.test/assets/projects/1/refs/character.png';
    $stager = makeBatchReferenceImageStager(BatchReferenceImageStager::prefixForBatch());
    $staged = $stager->stageAll([$sourceUrl]);

    expect($staged)->toHaveCount(1)
        ->and($staged[0]['fileUri'])->toStartWith('gs://test-batch-bucket/batch-refs/')
        ->and($staged[0]['mimeType'])->toBe('image/png');
});

it('identifies terminal vertex batch states in eloquent models', function () {
    $job = TestVertexBatchJob::query()->create([
        'vertex_state' => 'JOB_STATE_SUCCEEDED',
        'vertex_job_name' => 'projects/test/jobs/1',
    ]);

    expect($job->isTerminal())->toBeTrue();
});
