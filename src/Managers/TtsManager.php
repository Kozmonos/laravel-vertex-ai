<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Managers;

use Kozmonos\VertexAi\Support\ConfigValue;
use Kozmonos\VertexAi\Contracts\TextToSpeechProvider;
use Kozmonos\VertexAi\Contracts\UsageRecorder;
use Kozmonos\VertexAi\Enums\AiProvider;
use Kozmonos\VertexAi\Google\GoogleCloudTtsProvider;
use Kozmonos\VertexAi\Usage\Recording\UsageRecordingTtsProvider;
use Kozmonos\VertexAi\Vertex\VertexGeminiTtsProvider;
use Illuminate\Support\Manager;

class TtsManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return ConfigValue::string($this->config->get('vertex-ai.tts.default'), 'google');
    }

    protected function createVertexDriver(): TextToSpeechProvider
    {
        return new UsageRecordingTtsProvider(
            $this->container->make(VertexGeminiTtsProvider::class),
            $this->container->make(UsageRecorder::class),
            AiProvider::Vertex,
            'gemini-2.5-flash-preview-tts',
        );
    }

    protected function createGoogleDriver(): TextToSpeechProvider
    {
        return new UsageRecordingTtsProvider(
            $this->container->make(GoogleCloudTtsProvider::class),
            $this->container->make(UsageRecorder::class),
            AiProvider::Google,
            'google-cloud-tts',
        );
    }
}
