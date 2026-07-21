<?php

declare(strict_types=1);

$defaults = require dirname(__DIR__).'/../vertex-ai/config/vertex-ai.php';

return array_replace_recursive($defaults, [
    'text' => array_replace($defaults['text'], [
        'default' => env('VERTEX_AI_TEXT_PROVIDER', 'vertex'),
    ]),
    'image' => array_replace($defaults['image'], [
        'default' => env('VERTEX_AI_IMAGE_PROVIDER', 'vertex'),
    ]),
    'video' => array_replace($defaults['video'], [
        'default' => env('VERTEX_AI_VIDEO_PROVIDER', 'vertex'),
    ]),
    'tts' => array_replace($defaults['tts'], [
        'default' => env('VERTEX_AI_TTS_PROVIDER', 'google'),
    ]),
    'stt' => [
        'default' => env('VERTEX_AI_STT_PROVIDER', 'deepgram'),
    ],
    'vertex' => array_replace_recursive($defaults['vertex'], [
        'project_id' => env('VERTEX_PROJECT_ID', ''),
        'location' => env('VERTEX_LOCATION', 'global'),
        'credentials_path' => env('VERTEX_CREDENTIALS_PATH', env('GOOGLE_CREDENTIALS_PATH', '')),
        'forced_access_token' => env('VERTEX_FORCED_ACCESS_TOKEN'),
        'batch' => array_replace_recursive($defaults['vertex']['batch'], [
            'gcs_bucket' => env('VERTEX_BATCH_GCS_BUCKET', ''),
            'reference_cache_ttl' => (int) env('VERTEX_BATCH_REFERENCE_CACHE_TTL', 604800),
        ]),
    ]),
    'google' => array_replace_recursive($defaults['google'], [
        'credentials_path' => env('GOOGLE_CREDENTIALS_PATH', ''),
    ]),
    'deepgram' => array_replace_recursive($defaults['deepgram'], [
        'api_key' => env('DEEPGRAM_API_KEY', ''),
    ]),
    'outbound' => [
        'allowed_hosts' => array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            explode(',', (string) env('AI_OUTBOUND_ALLOWED_HOSTS', '')),
        ))),
    ],
]);
