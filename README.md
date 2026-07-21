# kozmonos/laravel-vertex-ai

[![CI](https://github.com/Kozmonos/laravel-vertex-ai/actions/workflows/ci.yml/badge.svg)](https://github.com/Kozmonos/laravel-vertex-ai/actions/workflows/ci.yml)

Laravel bridge for [`kozmonos/vertex-ai`](https://github.com/Kozmonos/vertex-ai): service provider, managers, Eloquent batch helpers, and S3 reference image loading.

## Install

```bash
composer require kozmonos/laravel-vertex-ai
php artisan vendor:publish --tag=vertex-ai-config
```

## Environment

| Variable | Purpose |
|---|---|
| `VERTEX_PROJECT_ID` | GCP project |
| `VERTEX_CREDENTIALS_PATH` | Service account JSON |
| `VERTEX_BATCH_GCS_BUCKET` | Batch input/output bucket |
| `VERTEX_FORCED_ACCESS_TOKEN` | Local/testing auth override only |
| `AI_OUTBOUND_ALLOWED_HOSTS` | Comma-separated host allowlist for reference image downloads |
| `DEEPGRAM_API_KEY` | STT |

`VERTEX_FORCED_ACCESS_TOKEN` is ignored outside `local` and `testing`.

## Managers

```php
use Kozmonos\LaravelVertexAi\Managers\TextGenerationManager;

app(TextGenerationManager::class)->driver()->generateText(...);
```

In `testing`, register `fake` drivers automatically (`config('vertex-ai.text.default') = 'fake'`).

## Eloquent batch polling / cancel

```php
use Kozmonos\LaravelVertexAi\Batch\Actions\PollSingleVertexBatchJobAction;
use Kozmonos\LaravelVertexAi\Batch\Concerns\HasVertexBatchJobRecord;

// Model implements VertexBatchJobRecord via HasVertexBatchJobRecord trait

app(PollSingleVertexBatchJobAction::class)->execute($job, lockPrefix: 'vertex-batch');
```

## Custom usage recording

Bind your app implementation to `Kozmonos\VertexAi\Contracts\UsageRecorder` in a service provider **after** `LaravelVertexAiServiceProvider`.

## Security

Read [SECURITY.md](SECURITY.md) and the core package [SECURITY.md](https://github.com/kozmonos/vertex-ai/blob/main/SECURITY.md).

## Tests

```bash
composer install
composer test
composer analyse
```

## License

MIT. See [LICENSE](LICENSE).
