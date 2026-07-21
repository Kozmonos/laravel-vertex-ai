<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kozmonos\LaravelVertexAi\Models\AiUsageEvent;

trait HasAiUsageEvents
{
    /**
     * @return MorphMany<AiUsageEvent, $this>
     */
    public function aiUsageEvents(): MorphMany
    {
        return $this->morphMany(AiUsageEvent::class, 'subject');
    }
}
