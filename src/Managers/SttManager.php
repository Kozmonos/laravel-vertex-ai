<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Managers;

use Kozmonos\VertexAi\Support\ConfigValue;
use Kozmonos\VertexAi\Contracts\SpeechToTextProvider;
use Kozmonos\VertexAi\Contracts\UsageRecorder;
use Kozmonos\VertexAi\Deepgram\DeepgramSttProvider;
use Kozmonos\VertexAi\Enums\AiProvider;
use Kozmonos\VertexAi\Usage\Recording\UsageRecordingSttProvider;
use Illuminate\Support\Manager;

class SttManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return ConfigValue::string($this->config->get('vertex-ai.stt.default'), 'deepgram');
    }

    protected function createDeepgramDriver(): SpeechToTextProvider
    {
        return new UsageRecordingSttProvider(
            $this->container->make(DeepgramSttProvider::class),
            $this->container->make(UsageRecorder::class),
            AiProvider::Deepgram,
            'deepgram',
        );
    }
}
