<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property array<string, int|float|bool>|null $usage
 */
class AiUsageEvent extends Model
{
  /**
   * @var list<string>
   */
    protected $fillable = [
        'subject_type',
        'subject_id',
        'scope',
        'provider',
        'capability',
        'model_config_key',
        'model_id',
        'dispatch_mode',
        'account_context',
        'usage',
        'cost_usd',
        'pricing_status',
        'batch_discount_applied',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'account_context' => 'array',
            'usage' => 'array',
            'batch_discount_applied' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
