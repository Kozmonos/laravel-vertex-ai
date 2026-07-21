<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Managers;

use Kozmonos\VertexAi\Support\ConfigValue;
use Kozmonos\VertexAi\Contracts\ImageGenerationProvider;
use Kozmonos\VertexAi\Contracts\UsageRecorder;
use Kozmonos\VertexAi\Contracts\VertexHttpTransportInterface;
use Kozmonos\VertexAi\Enums\AiProvider;
use Kozmonos\VertexAi\Support\AiModelRegistry;
use Kozmonos\VertexAi\Usage\Recording\UsageRecordingImageProvider;
use Kozmonos\VertexAi\Vertex\VertexImageProvider;
use Illuminate\Support\Manager;

class ImageGenerationManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return ConfigValue::string($this->config->get('vertex-ai.image.default'), 'vertex');
    }

    protected function createVertexDriver(): ImageGenerationProvider
    {
        return new UsageRecordingImageProvider(
            new VertexImageProvider(
                $this->container->make(VertexHttpTransportInterface::class),
                $this->container->make(AiModelRegistry::class),
                $this->container->make(\Kozmonos\VertexAi\Contracts\ReferenceImageLoader::class),
            ),
            $this->container->make(UsageRecorder::class),
            $this->container->make(AiModelRegistry::class),
            AiProvider::Vertex,
        );
    }
}
