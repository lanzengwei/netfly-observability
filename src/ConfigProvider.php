<?php

declare(strict_types=1);

namespace Netfly\Observability;

use Netfly\Observability\Aspect\RedisAspect;
use Netfly\Observability\Contract\FeatureSwitchInterface;
use Netfly\Observability\Contract\ProjectContextInterface;
use Netfly\Observability\Contract\TraceContextInterface;
use Netfly\Observability\Controller\MetricsController;
use Netfly\Observability\Listener\AmqpListener;
use Netfly\Observability\Listener\DbQueryListener;
use Netfly\Observability\Listener\RedisCommandListener;
use Netfly\Observability\Middleware\HttpObservabilityMiddleware;
use Netfly\Observability\Process\SwooleMetricsProcess;
use Netfly\Observability\Support\FeatureSwitch;
use Netfly\Observability\Support\ProjectContext;
use Netfly\Observability\Support\TraceContext;

class ConfigProvider
{
    public function __invoke(): array
    {
        $config = $this->loadDefaultConfig();
        $enabled = (bool) ($config['enabled'] ?? true);

        $result = [
            'dependencies' => [
                FeatureSwitchInterface::class => FeatureSwitch::class,
                ProjectContextInterface::class => ProjectContext::class,
                TraceContextInterface::class => TraceContext::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for netfly-observability.',
                    'source' => __DIR__ . '/../publish/observability.php',
                    'destination' => BASE_PATH . '/config/autoload/observability.php',
                ],
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];

        if (! $enabled) {
            return $result;
        }

        $result['listeners'] = array_values(array_filter([
            class_exists('Hyperf\\Database\\Events\\QueryExecuted') ? DbQueryListener::class : null,
            RedisCommandListener::class,
            class_exists('Hyperf\\Amqp\\Event\\AfterProduce') ? AmqpListener::class : null,
        ]));

        $result['middlewares'] = [
            'http' => [
                HttpObservabilityMiddleware::class,
            ],
        ];

        $result['processes'] = [
            SwooleMetricsProcess::class,
        ];

        $result['aspects'] = [];
        if (! class_exists('Hyperf\\Redis\\Event\\CommandExecuted')) {
            $result['aspects'][] = RedisAspect::class;
        }

        $result['dependencies'][MetricsController::class] = MetricsController::class;

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDefaultConfig(): array
    {
        $publishFile = __DIR__ . '/../publish/observability.php';
        if (is_file($publishFile)) {
            $config = require $publishFile;
            if (is_array($config)) {
                return $config;
            }
        }

        return ['enabled' => true];
    }
}
