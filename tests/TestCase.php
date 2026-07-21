<?php

declare(strict_types=1);

namespace Kozmonos\LaravelVertexAi\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kozmonos\LaravelVertexAi\LaravelVertexAiServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    protected function getPackageProviders($app): array
    {
        return [LaravelVertexAiServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $credentialsPath = sys_get_temp_dir().'/laravel-vertex-ai-test-credentials.json';

        if (! is_file($credentialsPath)) {
            file_put_contents($credentialsPath, json_encode([
                'type' => 'service_account',
                'project_id' => 'demo-project',
                'client_email' => 'demo@demo.iam.gserviceaccount.com',
            ], JSON_THROW_ON_ERROR));
        }

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('vertex-ai.vertex.forced_access_token', 'test-token');
        $app['config']->set('vertex-ai.vertex.project_id', 'demo-project');
        $app['config']->set('vertex-ai.vertex.credentials_path', $credentialsPath);
        $app['config']->set('vertex-ai.vertex.batch.gcs_bucket', 'test-batch-bucket');
    }
}
