<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'enabled' => (bool) env('OBSERVABILITY_ENABLED', true),
    'project' => env('OBSERVABILITY_PROJECT', env('APP_NAME', 'default')),
    'service' => env('OBSERVABILITY_SERVICE', env('APP_NAME', 'hyperf')),

    'modules' => [
        'http' => (bool) env('OBSERVABILITY_HTTP', true),
        'mysql' => (bool) env('OBSERVABILITY_MYSQL', true),
        'redis' => (bool) env('OBSERVABILITY_REDIS', true),
        'rabbitmq' => (bool) env('OBSERVABILITY_RABBITMQ', true),
        'swoole' => (bool) env('OBSERVABILITY_SWOOLE', true),
        'logging' => (bool) env('OBSERVABILITY_LOGGING', true),
    ],

    'metrics' => [
        'host' => '0.0.0.0',
        'port' => (int) env('OBSERVABILITY_METRICS_PORT', 9502),
        'path' => '/metrics',
        'namespace' => 'netfly',
    ],

    'logging' => [
        'driver' => env('OBSERVABILITY_LOG_DRIVER', 'stdout'),
        'file' => env('OBSERVABILITY_LOG_FILE', BASE_PATH . '/runtime/logs/observability.log'),
        'loki' => [
            'endpoint' => env('LOKI_PUSH_URL', 'http://loki:3100/loki/api/v1/push'),
            'batch_size' => 100,
            'timeout' => 2.0,
        ],
    ],
];
