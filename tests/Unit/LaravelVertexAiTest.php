<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Kozmonos\LaravelVertexAi\Batch\Actions\CancelVertexBatchJobAction;
use Kozmonos\LaravelVertexAi\Batch\Actions\PollSingleVertexBatchJobAction;
use Kozmonos\LaravelVertexAi\Managers\ImageGenerationManager;
use Kozmonos\LaravelVertexAi\Managers\SttManager;
use Kozmonos\LaravelVertexAi\Managers\TextGenerationManager;
use Kozmonos\LaravelVertexAi\Managers\TtsManager;
use Kozmonos\LaravelVertexAi\Managers\VideoGenerationManager;
use Kozmonos\LaravelVertexAi\Tests\Support\MarkTestVertexBatchJobCancelled;
use Kozmonos\LaravelVertexAi\Tests\Support\TestVertexBatchJob;
use Kozmonos\VertexAi\Batch\Actions\PollVertexBatchJobStatusAction;
use Kozmonos\VertexAi\Batch\Exceptions\VertexBatchJobAlreadyFinishedException;
use Kozmonos\VertexAi\Batch\Support\VertexBatchJobState;
use Kozmonos\VertexAi\Contracts\BatchJobProvider;
use Kozmonos\VertexAi\Contracts\ImageGenerationProvider;
use Kozmonos\VertexAi\Contracts\SpeechToTextProvider;
use Kozmonos\VertexAi\Contracts\TextGenerationProvider;
use Kozmonos\VertexAi\Contracts\TextToSpeechProvider;
use Kozmonos\VertexAi\Contracts\VideoGenerationProvider;
use Kozmonos\VertexAi\Fakes\FakeTextGenerationProvider;

uses(Kozmonos\LaravelVertexAi\Tests\TestCase::class);

it('resolves ai managers from the container', function () {
    Config::set('vertex-ai.text.default', 'fake');

    expect(app(TextGenerationManager::class)->driver())
        ->toBeInstanceOf(TextGenerationProvider::class)
        ->and(app(ImageGenerationManager::class)->driver())
        ->toBeInstanceOf(ImageGenerationProvider::class)
        ->and(app(VideoGenerationManager::class)->driver())
        ->toBeInstanceOf(VideoGenerationProvider::class)
        ->and(app(TtsManager::class)->driver())
        ->toBeInstanceOf(TextToSpeechProvider::class)
        ->and(app(SttManager::class)->driver())
        ->toBeInstanceOf(SpeechToTextProvider::class);
});

it('uses fake text provider in testing when configured', function () {
    Config::set('vertex-ai.text.default', 'fake');

    expect(app(TextGenerationManager::class)->driver())->toBeInstanceOf(FakeTextGenerationProvider::class);
});

it('updates a batch job from vertex poll status', function (): void {
    $job = TestVertexBatchJob::query()->create([
        'vertex_job_name' => 'projects/test/locations/us/batchJobs/123',
        'vertex_state' => 'JOB_STATE_RUNNING',
    ]);

    $pollStatus = mock(PollVertexBatchJobStatusAction::class);
    $pollStatus->shouldReceive('execute')
        ->once()
        ->with($job->vertex_job_name)
        ->andReturn([
            'state' => VertexBatchJobState::SUCCEEDED,
            'completionStats' => ['successfulCount' => 2, 'failedCount' => 0],
        ]);

    app()->instance(PollVertexBatchJobStatusAction::class, $pollStatus);

    $result = app(PollSingleVertexBatchJobAction::class)->execute($job, 'test-batch-poll');

    expect($result->isSucceeded())->toBeTrue()
        ->and($job->fresh()->vertex_state)->toBe(VertexBatchJobState::SUCCEEDED)
        ->and($job->fresh()->succeeded_count)->toBe(2);
});

it('skips poll when vertex job name is missing', function (): void {
    $job = TestVertexBatchJob::query()->create([
        'vertex_job_name' => null,
    ]);

    $result = app(PollSingleVertexBatchJobAction::class)->execute($job, 'test-batch-poll');

    expect($result->skipped)->toBeTrue();
});

it('cancels a non-terminal batch job through the provider', function (): void {
    $job = TestVertexBatchJob::query()->create([
        'vertex_job_name' => 'projects/test/locations/us/batchJobs/123',
        'vertex_state' => 'JOB_STATE_RUNNING',
    ]);

    $provider = mock(BatchJobProvider::class);
    $provider->shouldReceive('cancel')
        ->once()
        ->with($job->vertex_job_name);

    app()->instance(BatchJobProvider::class, $provider);

    $cancelled = app(CancelVertexBatchJobAction::class)->execute(
        $job,
        new MarkTestVertexBatchJobCancelled,
    );

    expect($cancelled)->toBeInstanceOf(TestVertexBatchJob::class)
        ->and($cancelled->fresh()->vertex_state)->toBe('JOB_STATE_CANCELLED');
});

it('throws when cancelling a terminal batch job', function (): void {
    $job = TestVertexBatchJob::query()->create([
        'vertex_job_name' => 'projects/test/locations/us/batchJobs/123',
        'vertex_state' => 'JOB_STATE_SUCCEEDED',
    ]);

    app(CancelVertexBatchJobAction::class)->execute(
        $job,
        new MarkTestVertexBatchJobCancelled,
    );
})->throws(VertexBatchJobAlreadyFinishedException::class);
