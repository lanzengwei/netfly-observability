<?php

declare(strict_types=1);

return [
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
            BASE_PATH . '/vendor/netfly/netfly-observability/src',
        ],
        'ignore_annotations' => [],
        'collectors' => [],
    ],
];
