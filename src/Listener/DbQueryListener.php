<?php

declare(strict_types=1);

namespace Netfly\Observability\Listener;

use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Contract\ListenerInterface;
use Netfly\Observability\Contract\FeatureSwitchInterface;
use Netfly\Observability\Logging\ObservabilityLoggerFactory;
use Netfly\Observability\Metric\DbMetricCollector;
use Netfly\Observability\Support\SqlSanitizer;

class DbQueryListener implements ListenerInterface
{
    /**
     * @var FeatureSwitchInterface
     */
    private $featureSwitch;

    /**
     * @var DbMetricCollector
     */
    private $collector;

    /**
     * @var SqlSanitizer
     */
    private $sqlSanitizer;

    /**
     * @var ObservabilityLoggerFactory
     */
    private $loggerFactory;

    public function __construct(
        FeatureSwitchInterface $featureSwitch,
        DbMetricCollector $collector,
        SqlSanitizer $sqlSanitizer,
        ObservabilityLoggerFactory $loggerFactory
    ) {
        $this->featureSwitch = $featureSwitch;
        $this->collector = $collector;
        $this->sqlSanitizer = $sqlSanitizer;
        $this->loggerFactory = $loggerFactory;
    }

    public function listen(): array
    {
        return [QueryExecuted::class];
    }

    public function process(object $event): void
    {
        if (! $this->featureSwitch->isModuleEnabled('mysql') || ! $event instanceof QueryExecuted) {
            return;
        }

        $operation = $this->sqlSanitizer->sanitize($event->sql);
        $connection = (string) ($event->connectionName ?? 'default');
        $durationSeconds = $event->time / 1000;

        $this->collector->record($connection, $operation, $durationSeconds);

        if ($this->featureSwitch->isModuleEnabled('logging')) {
            $this->loggerFactory->get('mysql')->debug('Database query executed', [
                'connection' => $connection,
                'operation' => $operation,
                'duration_ms' => $event->time,
            ]);
        }
    }
}
