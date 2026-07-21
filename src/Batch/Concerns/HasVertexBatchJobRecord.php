<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Batch\Concerns;

use Kozmonos\VertexAi\Batch\Support\VertexBatchJobState;
use RuntimeException;

/**
 * @property string|null $vertex_state
 * @property string|null $vertex_job_name
 */
trait HasVertexBatchJobRecord
{
    public function isTerminal(): bool
    {
        return VertexBatchJobState::isTerminal($this->vertex_state);
    }

    public function externalJobId(): string
    {
        $jobName = $this->vertex_job_name;

        if (! is_string($jobName) || $jobName === '') {
            throw $this->missingVertexBatchJobExternalId();
        }

        return $jobName;
    }

    abstract protected function missingVertexBatchJobExternalId(): RuntimeException;
}
