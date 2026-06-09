<?php

declare(strict_types=1);

namespace Netfly\Observability\Metric;

class HttpMetricCollector
{
    /**
     * @var MetricRegistry
     */
    private $registry;

    /**
     * @var \Prometheus\Counter|null
     */
    private $requestsTotal;

    /**
     * @var \Prometheus\Histogram|null
     */
    private $requestDuration;

    public function __construct(MetricRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function record(string $method, string $route, int $status, float $durationSeconds): void
    {
        $this->getRequestsTotal()->inc([
            $this->registry->getProjectLabel(),
            $method,
            $route,
            (string) $status,
        ]);

        $this->getRequestDuration()->observe($durationSeconds, [
            $this->registry->getProjectLabel(),
            $method,
            $route,
        ]);
    }

    private function getRequestsTotal(): \Prometheus\Counter
    {
        if ($this->requestsTotal === null) {
            $this->requestsTotal = $this->registry->counter(
                'http_requests_total',
                'Total HTTP requests',
                ['method', 'route', 'status']
            );
        }

        return $this->requestsTotal;
    }

    private function getRequestDuration(): \Prometheus\Histogram
    {
        if ($this->requestDuration === null) {
            $this->requestDuration = $this->registry->histogram(
                'http_request_duration_seconds',
                'HTTP request duration in seconds',
                ['method', 'route']
            );
        }

        return $this->requestDuration;
    }
}
