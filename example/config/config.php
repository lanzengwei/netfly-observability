<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'app_name' => env('APP_NAME', 'example-service'),
    'scan_cacheable' => env('SCAN_CACHEABLE', false),
];
