<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Contracts;

use Kozmonos\VertexAi\Usage\AiUsageFrame;

interface UsageEventAttributeMapper
{
    /**
     * @return array<string, mixed>
     */
    public function map(AiUsageFrame $frame): array;
}
