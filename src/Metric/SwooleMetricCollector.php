<?php

declare(strict_types=1);

namespace Netfly\Observability\Metric;

class SwooleMetricCollector
{
    /**
     * @var MetricRegistry
     */
    private $registry;

    /**
     * @var \Prometheus\Gauge|null
     */
    private $workerRequestTotal;

    /**
     * @var \Prometheus\Gauge|null
     */
    private $coroutineNum;

    /**
     * @var \Prometheus\Gauge|null
     */
    private $processMemory;

    /**
     * @var \Prometheus\Gauge|null
     */
    private $phpMemory;

    public function __construct(MetricRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function recordWorkerStats(int $workerId, int $requestCount, int $coroutineNum, int $memoryBytes): void
    {
        $project = $this->registry->getProjectLabel();

        $this->getWorkerRequestTotal()->set((float) $requestCount, [$project, (string) $workerId]);
        $this->getCoroutineNum()->set((float) $coroutineNum, [$project, (string) $workerId]);
        $this->getProcessMemory()->set((float) $memoryBytes, [$project, (string) $workerId]);
    }

    public function recordPhpMemory(int $memoryBytes): void
    {
        $this->getPhpMemory()->set((float) $memoryBytes, [$this->registry->getProjectLabel()]);
    }

    private function getWorkerRequestTotal(): \Prometheus\Gauge
    {
        if ($this->workerRequestTotal === null) {
            $this->workerRequestTotal = $this->registry->gauge(
                'swoole_worker_request_total',
                'Swoole worker request count',
                ['worker_id']
            );
        }

        return $this->workerRequestTotal;
    }

    private function getCoroutineNum(): \Prometheus\Gauge
    {
        if ($this->coroutineNum === null) {
            $this->coroutineNum = $this->registry->gauge(
                'swoole_coroutine_num',
                'Swoole coroutine count',
                ['worker_id']
            );
        }

        return $this->coroutineNum;
    }

    private function getProcessMemory(): \Prometheus\Gauge
    {
        if ($this->processMemory === null) {
            $this->processMemory = $this->registry->gauge(
                'process_memory_bytes',
                'Process memory usage in bytes',
                ['worker_id']
            );
        }

        return $this->processMemory;
    }

    private function getPhpMemory(): \Prometheus\Gauge
    {
        if ($this->phpMemory === null) {
            $this->phpMemory = $this->registry->gauge(
                'php_memory_usage_bytes',
                'PHP memory usage in bytes',
                []
            );
        }

        return $this->phpMemory;
    }
}
