<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Batch\Support;

use Closure;
use Kozmonos\VertexAi\Batch\Support\VertexBatchJobState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class VertexBatchJobsNeedingAttention
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  Closure(Builder<TModel>): void|null  $whenSucceeded
     * @return Builder<TModel>
     */
    public static function applyTo(
        Builder $query,
        ?Closure $whenSucceeded = null,
        string $vertexStateColumn = 'vertex_state',
    ): Builder {
        return $query->where(function (Builder $query) use ($whenSucceeded, $vertexStateColumn): void {
            $query->whereNull($vertexStateColumn)
                ->orWhereNotIn($vertexStateColumn, VertexBatchJobState::TERMINAL)
                ->orWhere(function (Builder $query) use ($whenSucceeded, $vertexStateColumn): void {
                    $query->where($vertexStateColumn, VertexBatchJobState::SUCCEEDED);

                    if ($whenSucceeded !== null) {
                        $whenSucceeded($query);
                    }
                });
        });
    }
}
