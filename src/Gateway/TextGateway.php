<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Gateway;

use Kozmonos\LaravelVertexAi\Managers\TextGenerationManager;
use Kozmonos\VertexAi\Contracts\TextGenerationProvider;
use Kozmonos\VertexAi\Data\TextGenerationConfig;
use Kozmonos\VertexAi\Data\TextGenerationResult;

final class TextGateway
{
    public function __construct(
        private readonly TextGenerationManager $manager,
    ) {}

    /**
     * @param  array<string, mixed>  $responseSchema
     */
    public function generate(
        string $systemPrompt,
        string $userPrompt,
        string $modelId,
        array $responseSchema = [],
        ?string $imageBase64 = null,
        ?TextGenerationConfig $config = null,
    ): TextGenerationResult {
        $provider = $this->manager->driver();

        if (! $provider instanceof TextGenerationProvider) {
            throw new \RuntimeException('Text generation provider is not configured.');
        }

        return $provider->generateText(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            modelId: $modelId,
            responseSchema: $responseSchema,
            imageBase64: $imageBase64,
            config: $config,
        );
    }
}
