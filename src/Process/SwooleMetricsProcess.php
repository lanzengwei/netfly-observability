<?php

declare(strict_types=1);

namespace Netfly\Observability\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Netfly\Observability\Contract\FeatureSwitchInterface;
use Netfly\Observability\Metric\SwooleMetricCollector;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;

#[Process(name: 'netfly-observability-swoole-metrics')]
class SwooleMetricsProcess extends AbstractProcess
{
    /**
     * @var FeatureSwitchInterface
     */
    private $featureSwitch;

    /**
     * @var SwooleMetricCollector
     */
    private $collector;

    public function __construct(
        ContainerInterface $container,
        FeatureSwitchInterface $featureSwitch,
        SwooleMetricCollector $collector
    ) {
        parent::__construct($container);
        $this->featureSwitch = $featureSwitch;
        $this->collector = $collector;
    }

    public function handle(): void
    {
        if (! $this->featureSwitch->isModuleEnabled('swoole')) {
            return;
        }

        while ($this->featureSwitch->isModuleEnabled('swoole')) {
            $this->collectMetrics();
            sleep(5);
        }
    }

    public function isEnable($server): bool
    {
        return $this->featureSwitch->isModuleEnabled('swoole');
    }

    private function collectMetrics(): void
    {
        $workerId = 0;
        $requestCount = 0;
        $coroutineNum = 0;
        $memoryBytes = memory_get_usage(true);

        if ($this->container->has(\Hyperf\Server\ServerFactory::class)) {
            $server = $this->container->get(\Hyperf\Server\ServerFactory::class)->getServer()->getServer();
            if ($server && method_exists($server, 'stats')) {
                $stats = $server->stats();
                $coroutineNum = (int) ($stats['coroutine_num'] ?? 0);
                $requestCount = (int) ($stats['request_count'] ?? $stats['requests_total'] ?? 0);
                $memoryBytes = (int) ($stats['worker_memory_usage'] ?? $memoryBytes);
            }
        }

        if (class_exists(Coroutine::class) && method_exists(Coroutine::class, 'stats')) {
            $coStats = Coroutine::stats();
            $coroutineNum = (int) ($coStats['coroutine_num'] ?? $coroutineNum);
        }

        $this->collector->recordWorkerStats($workerId, $requestCount, $coroutineNum, $memoryBytes);
        $this->collector->recordPhpMemory(memory_get_usage(true));
    }
}
