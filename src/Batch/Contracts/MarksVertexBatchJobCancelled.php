<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Batch\Contracts;

use Illuminate\Database\Eloquent\Model;

interface MarksVertexBatchJobCancelled
{
    public function execute(Model $job, ?string $error = null): Model;
}
