<?php

declare(strict_types=1);

namespace Netfly\Observability\Metric;

class RedisMetricCollector
{
    /**
     * @var MetricRegistry
     */
    private $registry;

    /**
     * @var \Prometheus\Counter|null
     */
    private $commandsTotal;

    /**
     * @var \Prometheus\Histogram|null
     */
    private $commandDuration;

    public function __construct(MetricRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function record(string $command, float $durationSeconds): void
    {
        $this->getCommandsTotal()->inc([
            $this->registry->getProjectLabel(),
            $command,
        ]);

        $this->getCommandDuration()->observe($durationSeconds, [
            $this->registry->getProjectLabel(),
            $command,
        ]);
    }

    private function getCommandsTotal(): \Prometheus\Counter
    {
        if ($this->commandsTotal === null) {
            $this->commandsTotal = $this->registry->counter(
                'redis_commands_total',
                'Total Redis commands',
                ['command']
            );
        }

        return $this->commandsTotal;
    }

    private function getCommandDuration(): \Prometheus\Histogram
    {
        if ($this->commandDuration === null) {
            $this->commandDuration = $this->registry->histogram(
                'redis_command_duration_seconds',
                'Redis command duration in seconds',
                ['command']
            );
        }

        return $this->commandDuration;
    }
}
