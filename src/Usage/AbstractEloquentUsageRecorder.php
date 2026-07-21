<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Usage;

use Illuminate\Contracts\Config\Repository;
use Kozmonos\LaravelVertexAi\Contracts\UsageEventAttributeMapper;
use Kozmonos\VertexAi\Contracts\UsageRecorder;
use Kozmonos\VertexAi\Pricing\AiCostCalculator;
use Kozmonos\VertexAi\Support\AiModelRegistry;
use Kozmonos\VertexAi\Usage\UsageRecord;

abstract class AbstractEloquentUsageRecorder implements UsageRecorder
{
    public function __construct(
        protected readonly AiUsageManager $usageManager,
        protected readonly AiCostCalculator $costCalculator,
        protected readonly AiModelRegistry $modelRegistry,
        protected readonly Repository $config,
        protected readonly ?UsageEventAttributeMapper $attributeMapper = null,
    ) {}

    public function record(UsageRecord $record): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $frame = $this->usageManager->currentFrame();
        $dispatchMode = $frame->dispatchMode;
        $modelConfigKey = $frame->modelConfigKey ?? $record->modelConfigKeyOrId;
        $modelId = $record->resolvedModelId
            ?? $frame->modelId
            ?? $this->modelRegistry->resolve($record->capability->value, $record->modelConfigKeyOrId);

        $calculation = $this->costCalculator->calculate($modelId, $record->usage, $dispatchMode);

        $attributes = array_merge([
            'provider' => $record->provider->value,
            'capability' => $record->capability->value,
            'model_config_key' => $modelConfigKey,
            'model_id' => $modelId,
            'dispatch_mode' => $dispatchMode->value,
            'usage' => $record->usage->toStorageArray(),
            'cost_usd' => $calculation->costUsd,
            'pricing_status' => $calculation->status->value,
            'batch_discount_applied' => $calculation->batchDiscountApplied,
            'subject_type' => $frame->subject?->type,
            'subject_id' => $frame->subject?->id,
            'occurred_at' => now(),
        ], $this->attributeMapper?->map($frame) ?? [], $this->extraAttributes($frame, $record));

        $modelClass = $this->model();

        $modelClass::query()->create($attributes);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraAttributes(\Kozmonos\VertexAi\Usage\AiUsageFrame $frame, UsageRecord $record): array
    {
        return [];
    }

    abstract protected function model(): string;

    private function isEnabled(): bool
    {
        return (bool) $this->config->get('vertex-ai.usage.enabled', true);
    }
}
