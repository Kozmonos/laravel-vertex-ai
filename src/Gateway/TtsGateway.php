<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Gateway;

use Kozmonos\LaravelVertexAi\Managers\TtsManager;
use Kozmonos\VertexAi\Contracts\TextToSpeechProvider;
use Kozmonos\VertexAi\Data\SpeechResult;

final class TtsGateway
{
    public function __construct(
        private readonly TtsManager $manager,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function synthesize(
        string $text,
        string $voiceId,
        array $options = [],
    ): SpeechResult {
        $provider = $this->manager->driver();

        if (! $provider instanceof TextToSpeechProvider) {
            throw new \RuntimeException('Text-to-speech provider is not configured.');
        }

        return $provider->synthesize(
            text: $text,
            voiceId: $voiceId,
            options: $options,
        );
    }
}
