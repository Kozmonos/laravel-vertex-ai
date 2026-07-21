<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Tests\Support;

use Kozmonos\LaravelVertexAi\Batch\Concerns\HasVertexBatchJobFields;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class TestVertexBatchJobWithFields extends Model
{
    use HasVertexBatchJobFields;

    protected $table = 'test_vertex_batch_jobs';

    public $timestamps = false;

    protected $guarded = [];

    protected function missingVertexBatchJobExternalId(): RuntimeException
    {
        return new RuntimeException('Vertex batch job external id is missing.');
    }
}
