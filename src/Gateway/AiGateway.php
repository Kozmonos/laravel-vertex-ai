<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Gateway;

use Illuminate\Database\Eloquent\Model;
use Kozmonos\LaravelVertexAi\Facades\AiUsage;
use Kozmonos\LaravelVertexAi\Managers\ImageGenerationManager;
use Kozmonos\LaravelVertexAi\Managers\SttManager;
use Kozmonos\LaravelVertexAi\Managers\TextGenerationManager;
use Kozmonos\LaravelVertexAi\Managers\TtsManager;
use Kozmonos\LaravelVertexAi\Managers\VideoGenerationManager;
use Kozmonos\VertexAi\Enums\UsageDispatchMode;

final class AiGateway
{
    public function __construct(
        private readonly TextGenerationManager $textGenerationManager,
        private readonly ImageGenerationManager $imageGenerationManager,
        private readonly VideoGenerationManager $videoGenerationManager,
        private readonly TtsManager $ttsManager,
        private readonly SttManager $sttManager,
    ) {}

    public function for(
        ?Model $subject = null,
        ?Model $project = null,
        UsageDispatchMode $mode = UsageDispatchMode::OnDemand,
    ): self {
        AiUsage::for($subject, $project, $mode);

        return $this;
    }

    public function text(): TextGateway
    {
        return new TextGateway($this->textGenerationManager);
    }

    public function image(): ImageGateway
    {
        return new ImageGateway($this->imageGenerationManager);
    }

    public function video(): VideoGateway
    {
        return new VideoGateway($this->videoGenerationManager);
    }

    public function tts(): TtsGateway
    {
        return new TtsGateway($this->ttsManager);
    }

    public function stt(): SttGateway
    {
        return new SttGateway($this->sttManager);
    }
}
