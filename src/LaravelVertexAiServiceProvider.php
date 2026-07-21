<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi;

use Kozmonos\VertexAi\Batch\Actions\PollVertexBatchJobStatusAction;
use Kozmonos\LaravelVertexAi\Batch\Actions\CancelVertexBatchJobAction;
use Kozmonos\LaravelVertexAi\Batch\Actions\PollSingleVertexBatchJobAction;
use Kozmonos\LaravelVertexAi\Managers\ImageGenerationManager;
use Kozmonos\LaravelVertexAi\Managers\SttManager;
use Kozmonos\LaravelVertexAi\Managers\TextGenerationManager;
use Kozmonos\LaravelVertexAi\Managers\TtsManager;
use Kozmonos\LaravelVertexAi\Managers\VideoGenerationManager;
use Kozmonos\LaravelVertexAi\Support\LaravelS3ReferenceImageLoader;
use Kozmonos\VertexAi\Contracts\AccessTokenProvider;
use Kozmonos\VertexAi\Contracts\BatchJobGatewayInterface;
use Kozmonos\VertexAi\Contracts\BatchJobProvider;
use Kozmonos\VertexAi\Contracts\GcsObjectStoreInterface;
use Kozmonos\VertexAi\Contracts\ReferenceImageLoader;
use Kozmonos\VertexAi\Contracts\UsageRecorder;
use Kozmonos\VertexAi\Contracts\VertexHttpTransportInterface;
use Kozmonos\VertexAi\Deepgram\DeepgramSttProvider;
use Kozmonos\VertexAi\Fakes\FakeImageGenerationProvider;
use Kozmonos\VertexAi\Fakes\FakeSpeechToTextProvider;
use Kozmonos\VertexAi\Fakes\FakeTextGenerationProvider;
use Kozmonos\VertexAi\Fakes\FakeTextToSpeechProvider;
use Kozmonos\VertexAi\Fakes\FakeVideoGenerationProvider;
use Kozmonos\VertexAi\Google\GoogleCloudTtsProvider;
use Kozmonos\VertexAi\Infrastructure\Auth\GoogleAuthTokenProvider;
use Kozmonos\VertexAi\Infrastructure\Batch\GoogleVertexBatchJobGateway;
use Kozmonos\VertexAi\Infrastructure\Gcs\GoogleCloudGcsObjectStore;
use Kozmonos\VertexAi\Infrastructure\Http\VertexHttpTransport;
use Kozmonos\VertexAi\Support\AiModelRegistry;
use Kozmonos\VertexAi\Support\ConfigValue;
use Kozmonos\VertexAi\Support\ArrayTtsVoiceCatalog;
use Kozmonos\VertexAi\Support\HttpReferenceImageLoader;
use Kozmonos\VertexAi\Usage\NullUsageRecorder;
use Kozmonos\VertexAi\Vertex\VertexBatchService;
use Kozmonos\VertexAi\Vertex\VertexConfig;
use Kozmonos\VertexAi\Vertex\VertexGeminiTtsProvider;
use Kozmonos\VertexAi\Vertex\VertexImageProvider;
use Kozmonos\VertexAi\Vertex\VertexTextProvider;
use Kozmonos\VertexAi\Vertex\VertexVideoProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class LaravelVertexAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vertex-ai.php', 'vertex-ai');

        $this->app->singleton(VertexConfig::class, function (Application $app): VertexConfig {
            /** @var array<string, mixed> $config */
            $config = $this->configRepository($app)->get('vertex-ai', []);

            return VertexConfig::fromArray($config, base_path());
        });

        $this->app->singleton(AiModelRegistry::class, function (Application $app): AiModelRegistry {
            /** @var array<string, mixed> $config */
            $config = $this->configRepository($app)->get('vertex-ai', []);

            return new AiModelRegistry($config);
        });

        $this->app->singleton(AccessTokenProvider::class, function (Application $app): AccessTokenProvider {
            $forced = $this->configRepository($app)->get('vertex-ai.vertex.forced_access_token');
            $mayForceToken = $app->environment('local', 'testing');
            $forcedToken = $mayForceToken && is_string($forced) && $forced !== '' ? $forced : null;

            return new GoogleAuthTokenProvider(
                $app->make(VertexConfig::class),
                $forcedToken,
            );
        });

        $this->app->singleton(VertexHttpTransportInterface::class, VertexHttpTransport::class);

        $this->app->singleton(GcsObjectStoreInterface::class, GoogleCloudGcsObjectStore::class);
        $this->app->singleton(BatchJobGatewayInterface::class, GoogleVertexBatchJobGateway::class);

        $this->app->singleton(VertexBatchService::class, function (Application $app): VertexBatchService {
            return new VertexBatchService(
                $app->make(VertexConfig::class),
                $app->make(GcsObjectStoreInterface::class),
                $app->make(BatchJobGatewayInterface::class),
            );
        });

        $this->app->singleton(
            BatchJobProvider::class,
            fn (Application $app): BatchJobProvider => $app->make(VertexBatchService::class),
        );

        $this->app->singleton(UsageRecorder::class, NullUsageRecorder::class);

        $this->app->singleton(ReferenceImageLoader::class, function (Application $app): ReferenceImageLoader {
            /** @var list<string> $allowedHosts */
            $allowedHosts = $this->configRepository($app)->get('vertex-ai.outbound.allowed_hosts', []);

            return new LaravelS3ReferenceImageLoader(
                new HttpReferenceImageLoader($allowedHosts),
            );
        });

        $this->app->singleton(ArrayTtsVoiceCatalog::class, function (Application $app): ArrayTtsVoiceCatalog {
            $voices = $this->configRepository($app)->get('tts_voices', []);
            /** @var list<array<string, mixed>> $voiceList */
            $voiceList = [];

            if (is_array($voices)) {
                foreach (array_values($voices) as $voice) {
                    if (! is_array($voice)) {
                        continue;
                    }

                    /** @var array<string, mixed> $voiceEntry */
                    $voiceEntry = $voice;
                    $voiceList[] = $voiceEntry;
                }
            }

            return new ArrayTtsVoiceCatalog($voiceList);
        });

        $this->app->singleton(GoogleCloudTtsProvider::class, function (Application $app): GoogleCloudTtsProvider {
            /** @var array<string, mixed> $google */
            $google = $this->configRepository($app)->get('vertex-ai.google', []);
            /** @var array<string, mixed> $http */
            $http = is_array($google['http'] ?? null) ? $google['http'] : [];

            return new GoogleCloudTtsProvider(
                tokenProvider: $app->make(AccessTokenProvider::class),
                voices: $app->make(ArrayTtsVoiceCatalog::class),
                endpoint: ConfigValue::string($google['tts_api_endpoint'] ?? null, 'https://texttospeech.googleapis.com/v1/text:synthesize'),
                timeout: ConfigValue::int($http['timeout'] ?? null, 120),
            );
        });

        $this->app->singleton(VertexGeminiTtsProvider::class, function (Application $app): VertexGeminiTtsProvider {
            return new VertexGeminiTtsProvider(
                tokenProvider: $app->make(AccessTokenProvider::class),
                voices: $app->make(ArrayTtsVoiceCatalog::class),
                config: $app->make(VertexConfig::class),
            );
        });

        $this->app->singleton(DeepgramSttProvider::class, function (Application $app): DeepgramSttProvider {
            /** @var array<string, mixed> $deepgram */
            $deepgram = $this->configRepository($app)->get('vertex-ai.deepgram', []);
            /** @var array<string, mixed> $http */
            $http = is_array($deepgram['http'] ?? null) ? $deepgram['http'] : [];

            return new DeepgramSttProvider(
                apiKey: ConfigValue::string($deepgram['api_key'] ?? null),
                endpoint: ConfigValue::string($deepgram['api_endpoint'] ?? null, 'https://api.deepgram.com/v1/listen'),
                timeout: ConfigValue::int($http['timeout'] ?? null, 300),
            );
        });

        $this->app->singleton(PollVertexBatchJobStatusAction::class);
        $this->app->singleton(PollSingleVertexBatchJobAction::class);
        $this->app->singleton(CancelVertexBatchJobAction::class);

        $this->app->singleton(TextGenerationManager::class);
        $this->app->singleton(ImageGenerationManager::class);
        $this->app->singleton(VideoGenerationManager::class);
        $this->app->singleton(TtsManager::class);
        $this->app->singleton(SttManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningUnitTests()) {
            $this->app->make(TextGenerationManager::class)->extend('fake', fn (): FakeTextGenerationProvider => new FakeTextGenerationProvider);
            $this->app->make(ImageGenerationManager::class)->extend('fake', fn (): FakeImageGenerationProvider => new FakeImageGenerationProvider);
            $this->app->make(VideoGenerationManager::class)->extend('fake', fn (): FakeVideoGenerationProvider => new FakeVideoGenerationProvider);
            $this->app->make(TtsManager::class)->extend('fake', fn (): FakeTextToSpeechProvider => new FakeTextToSpeechProvider);
            $this->app->make(SttManager::class)->extend('fake', fn (): FakeSpeechToTextProvider => new FakeSpeechToTextProvider);
        }

        $this->publishes([
            __DIR__.'/../config/vertex-ai.php' => config_path('vertex-ai.php'),
        ], 'vertex-ai-config');
    }

    private function configRepository(Application $app): Repository
    {
        return $app->make(Repository::class);
    }
}
