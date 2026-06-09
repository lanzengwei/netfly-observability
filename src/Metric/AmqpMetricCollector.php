<?php

declare(strict_types=1);

namespace Netfly\Observability\Metric;

class AmqpMetricCollector
{
    /**
     * @var MetricRegistry
     */
    private $registry;

    /**
     * @var \Prometheus\Counter|null
     */
    private $publishedTotal;

    /**
     * @var \Prometheus\Counter|null
     */
    private $consumedTotal;

    /**
     * @var \Prometheus\Histogram|null
     */
    private $consumeDuration;

    public function __construct(MetricRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function recordPublished(string $exchange, string $routingKey): void
    {
        $this->getPublishedTotal()->inc([
            $this->registry->getProjectLabel(),
            $exchange,
            $routingKey,
        ]);
    }

    public function recordConsumed(string $queue, string $result, float $durationSeconds): void
    {
        $this->getConsumedTotal()->inc([
            $this->registry->getProjectLabel(),
            $queue,
            $result,
        ]);

        $this->getConsumeDuration()->observe($durationSeconds, [
            $this->registry->getProjectLabel(),
            $queue,
        ]);
    }

    private function getPublishedTotal(): \Prometheus\Counter
    {
        if ($this->publishedTotal === null) {
            $this->publishedTotal = $this->registry->counter(
                'amqp_messages_published_total',
                'Total AMQP messages published',
                ['exchange', 'routing_key']
            );
        }

        return $this->publishedTotal;
    }

    private function getConsumedTotal(): \Prometheus\Counter
    {
        if ($this->consumedTotal === null) {
            $this->consumedTotal = $this->registry->counter(
                'amqp_messages_consumed_total',
                'Total AMQP messages consumed',
                ['queue', 'result']
            );
        }

        return $this->consumedTotal;
    }

    private function getConsumeDuration(): \Prometheus\Histogram
    {
        if ($this->consumeDuration === null) {
            $this->consumeDuration = $this->registry->histogram(
                'amqp_consume_duration_seconds',
                'AMQP consume duration in seconds',
                ['queue']
            );
        }

        return $this->consumeDuration;
    }
}
