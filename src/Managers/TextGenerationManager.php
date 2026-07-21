<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Managers;

use Kozmonos\VertexAi\Support\ConfigValue;
use Kozmonos\VertexAi\Contracts\TextGenerationProvider;
use Kozmonos\VertexAi\Contracts\UsageRecorder;
use Kozmonos\VertexAi\Contracts\VertexHttpTransportInterface;
use Kozmonos\VertexAi\Enums\AiProvider;
use Kozmonos\VertexAi\Support\AiModelRegistry;
use Kozmonos\VertexAi\Usage\Recording\UsageRecordingTextProvider;
use Kozmonos\VertexAi\Vertex\VertexTextProvider;
use Illuminate\Support\Manager;

class TextGenerationManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return ConfigValue::string($this->config->get('vertex-ai.text.default'), 'vertex');
    }

    protected function createVertexDriver(): TextGenerationProvider
    {
        return new UsageRecordingTextProvider(
            new VertexTextProvider(
                $this->container->make(VertexHttpTransportInterface::class),
                $this->container->make(AiModelRegistry::class),
            ),
            $this->container->make(UsageRecorder::class),
            $this->container->make(AiModelRegistry::class),
            AiProvider::Vertex,
        );
    }
}
