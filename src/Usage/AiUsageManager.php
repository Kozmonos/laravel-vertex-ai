<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Usage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;
use Kozmonos\LaravelVertexAi\Contracts\UsageScopeResolver;
use Kozmonos\VertexAi\Contracts\UsageRecorder;
use Kozmonos\VertexAi\Enums\UsageDispatchMode;
use Kozmonos\VertexAi\Usage\AiUsageContext;
use Kozmonos\VertexAi\Usage\AiUsageFrame;
use Kozmonos\VertexAi\Usage\NullUsageRecorder;
use Kozmonos\VertexAi\Usage\UsageSubject;

final class AiUsageManager
{
    public const CONTEXT_KEY = 'vertex_ai.usage';

    private const FAKE_RECORDER_KEY = 'vertex_ai.usage_recorder_fake';

    /**
     * @param  iterable<int, UsageScopeResolver>  $scopeResolvers
     */
    public function __construct(
        private readonly iterable $scopeResolvers,
    ) {}

    public function for(
        ?Model $subject = null,
        ?Model $project = null,
        UsageDispatchMode $mode = UsageDispatchMode::OnDemand,
    ): void {
        $this->applyFrame($this->buildFrame($subject, $project, $mode));
    }

    public function subject(?Model $subject): void
    {
        $frame = $this->currentFrame();
        $this->applyFrame(new AiUsageFrame(
            subject: $subject ? new UsageSubject($subject->getMorphClass(), $subject->getKey()) : null,
            scope: $frame->scope,
            dispatchMode: $frame->dispatchMode,
            modelConfigKey: $frame->modelConfigKey,
            modelId: $frame->modelId,
        ));
    }

    /**
     * @param  array<string, int|string|null>  $scope
     */
    public function scope(array $scope): void
    {
        $frame = $this->currentFrame();
        $this->applyFrame(new AiUsageFrame(
            subject: $frame->subject,
            scope: array_merge($frame->scope, $scope),
            dispatchMode: $frame->dispatchMode,
            modelConfigKey: $frame->modelConfigKey,
            modelId: $frame->modelId,
        ));
    }

    public function batch(): void
    {
        $frame = $this->currentFrame();
        $this->applyFrame(new AiUsageFrame(
            subject: $frame->subject,
            scope: $frame->scope,
            dispatchMode: UsageDispatchMode::Batch,
            modelConfigKey: $frame->modelConfigKey,
            modelId: $frame->modelId,
        ));
    }

    public function fake(): void
    {
        Context::add(self::FAKE_RECORDER_KEY, true);
        app()->instance(UsageRecorder::class, new NullUsageRecorder);
    }

    public function flush(): void
    {
        AiUsageContext::flush();
        Context::forget(self::CONTEXT_KEY);

        if (Context::get(self::FAKE_RECORDER_KEY) === true) {
            Context::forget(self::FAKE_RECORDER_KEY);
            app()->forgetInstance(UsageRecorder::class);
        }
    }

    public function currentFrame(): AiUsageFrame
    {
        $explicit = AiUsageContext::current();

        if ($explicit !== null) {
            return new AiUsageFrame(
                subject: $explicit->subject,
                scope: array_merge($this->resolvedScope(), $explicit->scope),
                dispatchMode: $explicit->dispatchMode,
                modelConfigKey: $explicit->modelConfigKey,
                modelId: $explicit->modelId,
            );
        }

        $stored = Context::get(self::CONTEXT_KEY);

        if (is_array($stored)) {
            return $this->frameFromArray($stored);
        }

        return new AiUsageFrame(scope: $this->resolvedScope());
    }

    private function buildFrame(
        ?Model $subject,
        ?Model $project,
        UsageDispatchMode $mode,
    ): AiUsageFrame {
        $scope = $this->resolvedScope();

        if ($project !== null) {
            $scope['project_id'] = $project->getKey();

            if (isset($project->organization_id)) {
                $scope['organization_id'] = $project->organization_id;
            }
        }

        if ($subject !== null) {
            $scope = array_merge($scope, $this->scopeFromSubjectRelations($subject));
        }

        return new AiUsageFrame(
            subject: $subject ? new UsageSubject($subject->getMorphClass(), $subject->getKey()) : null,
            scope: $scope,
            dispatchMode: $mode,
        );
    }

    private function applyFrame(AiUsageFrame $frame): void
    {
        AiUsageContext::set($frame);
        Context::add(self::CONTEXT_KEY, $this->frameToArray($frame));
    }

    /**
     * @return array<string, int|string|null>
     */
    private function resolvedScope(): array
    {
        $scope = [];

        foreach ($this->scopeResolvers as $resolver) {
            $scope = array_merge($scope, $resolver->resolve());
        }

        return $scope;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function scopeFromSubjectRelations(Model $subject): array
    {
        $scope = [];

        if (method_exists($subject, 'project')) {
            $project = $subject->relationLoaded('project')
                ? $subject->getRelation('project')
                : $subject->project()->first();

            if ($project instanceof Model) {
                $scope['project_id'] ??= $project->getKey();

                if (isset($project->organization_id)) {
                    $scope['organization_id'] ??= $project->organization_id;
                }
            }
        }

        if (isset($subject->organization_id)) {
            $scope['organization_id'] ??= $subject->organization_id;
        }

        if (isset($subject->project_id)) {
            $scope['project_id'] ??= $subject->project_id;
        }

        return $scope;
    }

    /**
     * @return array<string, mixed>
     */
    private function frameToArray(AiUsageFrame $frame): array
    {
        return [
            'subject_type' => $frame->subject?->type,
            'subject_id' => $frame->subject?->id,
            'scope' => $frame->scope,
            'dispatch_mode' => $frame->dispatchMode->value,
            'model_config_key' => $frame->modelConfigKey,
            'model_id' => $frame->modelId,
        ];
    }

    /**
     * @param  array<string, mixed>  $stored
     */
    private function frameFromArray(array $stored): AiUsageFrame
    {
        $subjectType = $stored['subject_type'] ?? null;
        $subjectId = $stored['subject_id'] ?? null;

        return new AiUsageFrame(
            subject: is_string($subjectType) && ($subjectId !== null && $subjectId !== '')
                ? new UsageSubject($subjectType, $subjectId)
                : null,
            scope: is_array($stored['scope'] ?? null) ? $stored['scope'] : $this->resolvedScope(),
            dispatchMode: UsageDispatchMode::tryFrom((string) ($stored['dispatch_mode'] ?? ''))
                ?? UsageDispatchMode::OnDemand,
            modelConfigKey: is_string($stored['model_config_key'] ?? null) ? $stored['model_config_key'] : null,
            modelId: is_string($stored['model_id'] ?? null) ? $stored['model_id'] : null,
        );
    }
}
