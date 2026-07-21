<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Kozmonos\LaravelVertexAi\Gateway\AiGateway;
use Kozmonos\LaravelVertexAi\Gateway\ImageGateway;
use Kozmonos\LaravelVertexAi\Gateway\SttGateway;
use Kozmonos\LaravelVertexAi\Gateway\TextGateway;
use Kozmonos\LaravelVertexAi\Gateway\TtsGateway;
use Kozmonos\LaravelVertexAi\Gateway\VideoGateway;
use Kozmonos\VertexAi\Enums\UsageDispatchMode;

/**
 * @method static TextGateway text()
 * @method static ImageGateway image()
 * @method static VideoGateway video()
 * @method static TtsGateway tts()
 * @method static SttGateway stt()
 * @method static AiGateway for(?Model $subject = null, ?Model $project = null, UsageDispatchMode $mode = UsageDispatchMode::OnDemand)
 *
 * @see AiGateway
 */
final class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiGateway::class;
    }
}
