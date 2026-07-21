<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Batch\Actions;

use Kozmonos\VertexAi\Batch\Actions\PollVertexBatchJobStatusAction;
use Kozmonos\VertexAi\Batch\Data\VertexBatchPollResult;
use Kozmonos\VertexAi\Batch\Support\VertexBatchJobState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class PollSingleVertexBatchJobAction
{
    public function __construct(
        private readonly PollVertexBatchJobStatusAction $pollVertexBatchJobStatusAction,
    ) {}

    /**
     * @param  callable(Model, array<string, mixed>): void|null  $afterStatusFetched
     */
    public function execute(
        Model $job,
        string $lockPrefix,
        ?callable $afterStatusFetched = null,
    ): VertexBatchPollResult {
        $vertexJobName = $job->getAttribute('vertex_job_name');

        if (! is_string($vertexJobName) || $vertexJobName === '') {
            return VertexBatchPollResult::skipped();
        }

        $jobKey = $job->getKey();

        /** @var VertexBatchPollResult $result */
        $result = Cache::lock($lockPrefix.':'.(is_scalar($jobKey) ? (string) $jobKey : ''), 120)->block(
            5,
            fn (): VertexBatchPollResult => $this->pollWithinLock($job, $vertexJobName, $afterStatusFetched),
        );

        return $result;
    }

    /**
     * @param  callable(Model, array<string, mixed>): void|null  $afterStatusFetched
     */
    private function pollWithinLock(
        Model $job,
        string $vertexJobName,
        ?callable $afterStatusFetched,
    ): VertexBatchPollResult {
        if ($job->getAttribute('vertex_state') === VertexBatchJobState::SUCCEEDED) {
            return VertexBatchPollResult::alreadySucceeded();
        }

        $status = $this->pollVertexBatchJobStatusAction->execute($vertexJobName);
        $state = is_string($status['state'] ?? null) ? $status['state'] : null;
        $completionStats = is_array($status['completionStats'] ?? null) ? $status['completionStats'] : [];
        $error = $status['error'] ?? null;
        $errorMessage = is_array($error) && is_string($error['message'] ?? null) ? $error['message'] : null;

        if ($afterStatusFetched !== null) {
            $afterStatusFetched($job, $status);
        }

        $job->forceFill([
            'vertex_state' => $state,
            'polled_at' => now(),
            'succeeded_count' => is_numeric($completionStats['successfulCount'] ?? null)
                ? (int) $completionStats['successfulCount']
                : $job->getAttribute('succeeded_count'),
            'failed_count' => is_numeric($completionStats['failedCount'] ?? null)
                ? (int) $completionStats['failedCount']
                : $job->getAttribute('failed_count'),
            'error' => $errorMessage,
        ])->save();

        return VertexBatchPollResult::fromPoll($state, $errorMessage);
    }
}
