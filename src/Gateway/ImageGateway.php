<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Gateway;

use Kozmonos\LaravelVertexAi\Managers\ImageGenerationManager;
use Kozmonos\VertexAi\Contracts\ImageGenerationProvider;
use Kozmonos\VertexAi\Data\ImageGenerationResult;

final class ImageGateway
{
    public function __construct(
        private readonly ImageGenerationManager $manager,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(
        string $prompt,
        string $modelId,
        ?string $aspectRatio = null,
        array $options = [],
    ): ImageGenerationResult {
        $provider = $this->manager->driver();

        if (! $provider instanceof ImageGenerationProvider) {
            throw new \RuntimeException('Image generation provider is not configured.');
        }

        return $provider->generateImage(
            prompt: $prompt,
            modelId: $modelId,
            aspectRatio: $aspectRatio,
            options: $options,
        );
    }
}
