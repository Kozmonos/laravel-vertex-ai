<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Batch\Actions;

use Kozmonos\LaravelVertexAi\Batch\Contracts\MarksVertexBatchJobCancelled;
use Kozmonos\VertexAi\Batch\Contracts\VertexBatchJobRecord;
use Kozmonos\VertexAi\Batch\Exceptions\VertexBatchJobAlreadyFinishedException;
use Kozmonos\VertexAi\Contracts\BatchJobProvider;
use Illuminate\Database\Eloquent\Model;

final class CancelVertexBatchJobAction
{
    public function __construct(
        private readonly BatchJobProvider $batchJobProvider,
    ) {}

    public function execute(
        VertexBatchJobRecord&Model $job,
        MarksVertexBatchJobCancelled $onCancelled,
        ?string $reason = null,
    ): Model {
        if ($job->isTerminal()) {
            $jobKey = $job->getKey();

            throw new VertexBatchJobAlreadyFinishedException(
                'Batch job ['.(is_scalar($jobKey) ? (string) $jobKey : 'unknown').'] is already finished and cannot be cancelled.',
            );
        }

        $this->batchJobProvider->cancel($job->externalJobId());

        return $onCancelled->execute(
            $job->fresh() ?? $job,
            $reason ?? 'Cancelled by user.',
        );
    }
}
