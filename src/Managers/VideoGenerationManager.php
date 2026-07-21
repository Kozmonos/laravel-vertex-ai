<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Managers;

use Kozmonos\VertexAi\Contracts\UsageRecorder;
use Kozmonos\VertexAi\Contracts\VertexHttpTransportInterface;
use Kozmonos\VertexAi\Contracts\VideoGenerationProvider;
use Kozmonos\VertexAi\Enums\AiProvider;
use Kozmonos\VertexAi\Support\ConfigValue;
use Kozmonos\VertexAi\Support\AiModelRegistry;
use Kozmonos\VertexAi\Usage\Recording\UsageRecordingVideoProvider;
use Kozmonos\VertexAi\Vertex\VertexVideoProvider;
use Illuminate\Support\Manager;

class VideoGenerationManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return ConfigValue::string($this->config->get('vertex-ai.video.default'), 'vertex');
    }

    protected function createVertexDriver(): VideoGenerationProvider
    {
        return new UsageRecordingVideoProvider(
            new VertexVideoProvider(
                $this->container->make(VertexHttpTransportInterface::class),
                $this->container->make(AiModelRegistry::class),
            ),
            $this->container->make(UsageRecorder::class),
            $this->container->make(AiModelRegistry::class),
            AiProvider::Vertex,
        );
    }
}
