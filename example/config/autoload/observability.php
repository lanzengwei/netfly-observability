<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'enabled' => (bool) env('OBSERVABILITY_ENABLED', true),
    'project' => env('OBSERVABILITY_PROJECT', env('APP_NAME', 'example-service')),
    'service' => env('OBSERVABILITY_SERVICE', 'api'),

    'modules' => [
        'http' => true,
        'mysql' => true,
        'redis' => true,
        'rabbitmq' => true,
        'swoole' => true,
        'logging' => true,
    ],

    'metrics' => [
        'host' => '0.0.0.0',
        'port' => (int) env('OBSERVABILITY_METRICS_PORT', 9502),
        'path' => '/metrics',
        'namespace' => 'netfly',
    ],

    'logging' => [
        'driver' => env('OBSERVABILITY_LOG_DRIVER', 'file'),
        'file' => env('OBSERVABILITY_LOG_FILE', BASE_PATH . '/runtime/logs/observability.log'),
        'loki' => [
            'endpoint' => env('LOKI_PUSH_URL', 'http://loki:3100/loki/api/v1/push'),
            'batch_size' => 10,
            'timeout' => 2.0,
        ],
    ],
];
