<?php

declare(strict_types=1);

use Kozmonos\VertexAi\Vertex\VertexConfig;

function makeTestVertexConfig(): VertexConfig
{
    $credentialsPath = sys_get_temp_dir().'/laravel-vertex-ai-test-credentials.json';

    if (! is_file($credentialsPath)) {
        file_put_contents($credentialsPath, json_encode([
            'type' => 'service_account',
            'project_id' => 'demo-project',
            'client_email' => 'demo@demo.iam.gserviceaccount.com',
        ], JSON_THROW_ON_ERROR));
    }

    return VertexConfig::fromArray([
        'vertex' => [
            'project_id' => 'demo-project',
            'location' => 'us-central1',
            'credentials_path' => $credentialsPath,
            'api_endpoint' => 'https://{location}-aiplatform.googleapis.com',
            'batch' => [
                'gcs_bucket' => 'test-batch-bucket',
            ],
        ],
    ]);
}
