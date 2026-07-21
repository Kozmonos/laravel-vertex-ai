<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Tests\Support;

use Kozmonos\LaravelVertexAi\Batch\Concerns\HasVertexBatchJobRecord;
use Kozmonos\LaravelVertexAi\Batch\Contracts\MarksVertexBatchJobCancelled;
use Kozmonos\VertexAi\Batch\Contracts\VertexBatchJobRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class TestVertexBatchJob extends Model implements VertexBatchJobRecord
{
    use HasVertexBatchJobRecord;

    protected $table = 'test_vertex_batch_jobs';

    public $timestamps = false;

    protected $guarded = [];

    protected function missingVertexBatchJobExternalId(): RuntimeException
    {
        return new RuntimeException('Vertex batch job external id is missing.');
    }
}

final class MarkTestVertexBatchJobCancelled implements MarksVertexBatchJobCancelled
{
    public function execute(Model $job, ?string $error = null): Model
    {
        $job->forceFill([
            'vertex_state' => 'JOB_STATE_CANCELLED',
            'error' => $error,
        ])->save();

        return $job;
    }
}
