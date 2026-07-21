<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Batch\Support;

use Kozmonos\VertexAi\Batch\Support\VertexBatchJobState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ActiveVertexBatchJobQuery
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function applyTo(Builder $query, string $vertexStateColumn = 'vertex_state'): Builder
    {
        return $query->where(function (Builder $query) use ($vertexStateColumn): void {
            $query->whereNull($vertexStateColumn)
                ->orWhereNotIn($vertexStateColumn, VertexBatchJobState::TERMINAL);
        });
    }

    /**
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     *
     * @param  HasMany<TRelatedModel, TDeclaringModel>  $relation
     */
    public static function latestActive(HasMany $relation): ?Model
    {
        /** @var Builder<Model> $query */
        $query = $relation->getQuery();

        return self::applyTo($query)
            ->latest('id')
            ->first();
    }
}
