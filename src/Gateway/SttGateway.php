<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Gateway;

use Kozmonos\LaravelVertexAi\Managers\SttManager;
use Kozmonos\VertexAi\Contracts\SpeechToTextProvider;
use Kozmonos\VertexAi\Data\TranscriptResult;

final class SttGateway
{
    public function __construct(
        private readonly SttManager $manager,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function transcribe(
        string $audioUrl,
        array $options = [],
    ): TranscriptResult {
        $provider = $this->manager->driver();

        if (! $provider instanceof SpeechToTextProvider) {
            throw new \RuntimeException('Speech-to-text provider is not configured.');
        }

        return $provider->transcribe(
            audioUrl: $audioUrl,
            options: $options,
        );
    }
}
