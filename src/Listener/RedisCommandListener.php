<?php

declare(strict_types=1);

namespace Netfly\Observability\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Netfly\Observability\Contract\FeatureSwitchInterface;
use Netfly\Observability\Logging\ObservabilityLoggerFactory;
use Netfly\Observability\Metric\RedisMetricCollector;

class RedisCommandListener implements ListenerInterface
{
    /**
     * @var FeatureSwitchInterface
     */
    private $featureSwitch;

    /**
     * @var RedisMetricCollector
     */
    private $collector;

    /**
     * @var ObservabilityLoggerFactory
     */
    private $loggerFactory;

    public function __construct(
        FeatureSwitchInterface $featureSwitch,
        RedisMetricCollector $collector,
        ObservabilityLoggerFactory $loggerFactory
    ) {
        $this->featureSwitch = $featureSwitch;
        $this->collector = $collector;
        $this->loggerFactory = $loggerFactory;
    }

    public function listen(): array
    {
        if (! class_exists('Hyperf\\Redis\\Event\\CommandExecuted')) {
            return [];
        }

        return ['Hyperf\\Redis\\Event\\CommandExecuted'];
    }

    public function process(object $event): void
    {
        if (! $this->featureSwitch->isModuleEnabled('redis')) {
            return;
        }

        if (! class_exists('Hyperf\\Redis\\Event\\CommandExecuted')) {
            return;
        }

        if (! $event instanceof \Hyperf\Redis\Event\CommandExecuted) {
            return;
        }

        $command = strtoupper((string) $event->command);
        $durationSeconds = $event->time / 1000;

        $this->collector->record($command, $durationSeconds);

        if ($this->featureSwitch->isModuleEnabled('logging')) {
            $this->loggerFactory->get('redis')->debug('Redis command executed', [
                'command' => $command,
                'duration_ms' => $event->time,
            ]);
        }
    }
}
