<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Usage;

use Kozmonos\LaravelVertexAi\Models\AiUsageEvent;

final class EloquentUsageRecorder extends AbstractEloquentUsageRecorder
{
    protected function model(): string
    {
        return AiUsageEvent::class;
    }
}
