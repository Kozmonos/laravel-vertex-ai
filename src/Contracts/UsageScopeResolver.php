<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Contracts;

interface UsageScopeResolver
{
    /**
     * @return array<string, int|string|null>
     */
    public function resolve(): array;
}
