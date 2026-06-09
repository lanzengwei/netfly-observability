<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;

class IndexController
{
    public function index(ResponseInterface $response)
    {
        return $response->json([
            'message' => 'netfly-observability example',
            'endpoints' => ['/demo', '/metrics'],
        ]);
    }
}
