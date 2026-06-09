<?php

declare(strict_types=1);

namespace Netfly\Observability\Metric;

class DbMetricCollector
{
    /**
     * @var MetricRegistry
     */
    private $registry;

    /**
     * @var \Prometheus\Counter|null
     */
    private $queriesTotal;

    /**
     * @var \Prometheus\Histogram|null
     */
    private $queryDuration;

    public function __construct(MetricRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function record(string $connection, string $operation, float $durationSeconds): void
    {
        $this->getQueriesTotal()->inc([
            $this->registry->getProjectLabel(),
            $connection,
            $operation,
        ]);

        $this->getQueryDuration()->observe($durationSeconds, [
            $this->registry->getProjectLabel(),
            $connection,
            $operation,
        ]);
    }

    private function getQueriesTotal(): \Prometheus\Counter
    {
        if ($this->queriesTotal === null) {
            $this->queriesTotal = $this->registry->counter(
                'db_queries_total',
                'Total database queries',
                ['connection', 'operation']
            );
        }

        return $this->queriesTotal;
    }

    private function getQueryDuration(): \Prometheus\Histogram
    {
        if ($this->queryDuration === null) {
            $this->queryDuration = $this->registry->histogram(
                'db_query_duration_seconds',
                'Database query duration in seconds',
                ['connection', 'operation']
            );
        }

        return $this->queryDuration;
    }
}
