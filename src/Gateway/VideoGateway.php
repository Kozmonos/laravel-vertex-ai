<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Gateway;

use Kozmonos\LaravelVertexAi\Managers\VideoGenerationManager;
use Kozmonos\VertexAi\Contracts\VideoGenerationProvider;
use Kozmonos\VertexAi\Data\VideoGenerationResult;

final class VideoGateway
{
    public function __construct(
        private readonly VideoGenerationManager $manager,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(
        string $prompt,
        string $modelId,
        array $options = [],
    ): VideoGenerationResult {
        $provider = $this->manager->driver();

        if (! $provider instanceof VideoGenerationProvider) {
            throw new \RuntimeException('Video generation provider is not configured.');
        }

        return $provider->generateVideo(
            prompt: $prompt,
            modelId: $modelId,
            options: $options,
        );
    }
}
