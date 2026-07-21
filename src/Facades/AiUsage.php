<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Kozmonos\LaravelVertexAi\Usage\AiUsageManager;
use Kozmonos\VertexAi\Enums\UsageDispatchMode;

/**
 * @method static void for(?Model $subject = null, ?Model $project = null, UsageDispatchMode $mode = UsageDispatchMode::OnDemand)
 * @method static void subject(?Model $subject)
 * @method static void scope(array<string, int|string|null> $scope)
 * @method static void batch()
 * @method static void fake()
 * @method static void flush()
 *
 * @see AiUsageManager
 */
final class AiUsage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiUsageManager::class;
    }
}
