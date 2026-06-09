<?php

declare(strict_types=1);

namespace Netfly\Observability\Metric;

use Hyperf\Contract\ConfigInterface;
use Netfly\Observability\Contract\ProjectContextInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class MetricRegistry
{
    /**
     * @var CollectorRegistry
     */
    private $registry;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var ProjectContextInterface
     */
    private $projectContext;

    public function __construct(ConfigInterface $config, ProjectContextInterface $projectContext)
    {
        $this->registry = new CollectorRegistry(new InMemory());
        $this->namespace = (string) $config->get('observability.metrics.namespace', 'netfly');
        $this->projectContext = $projectContext;
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getProjectLabel(): string
    {
        return $this->projectContext->getProject();
    }

    public function counter(string $name, string $help, array $labelNames): \Prometheus\Counter
    {
        return $this->registry->getOrRegisterCounter(
            $this->namespace,
            $name,
            $help,
            array_merge(['project'], $labelNames)
        );
    }

    public function histogram(string $name, string $help, array $labelNames, array $buckets = null): \Prometheus\Histogram
    {
        if ($buckets === null) {
            $buckets = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];
        }

        return $this->registry->getOrRegisterHistogram(
            $this->namespace,
            $name,
            $help,
            array_merge(['project'], $labelNames),
            $buckets
        );
    }

    public function gauge(string $name, string $help, array $labelNames): \Prometheus\Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->namespace,
            $name,
            $help,
            array_merge(['project'], $labelNames)
        );
    }
}
