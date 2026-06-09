<?php

declare(strict_types=1);

namespace Netfly\Observability\Aspect;

use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Netfly\Observability\Contract\FeatureSwitchInterface;
use Netfly\Observability\Logging\ObservabilityLoggerFactory;
use Netfly\Observability\Metric\RedisMetricCollector;

class RedisAspect extends AbstractAspect
{
    /** @var array<int, string> */
    public array $classes = [
        'Hyperf\\Redis\\Redis',
    ];

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

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        if (! $this->featureSwitch->isModuleEnabled('redis')) {
            return $proceedingJoinPoint->process();
        }

        $arguments = $proceedingJoinPoint->getArguments();
        $command = strtoupper((string) ($arguments[0] ?? 'unknown'));

        $start = microtime(true);
        $result = $proceedingJoinPoint->process();
        $duration = microtime(true) - $start;

        $this->collector->record($command, $duration);

        if ($this->featureSwitch->isModuleEnabled('logging')) {
            $this->loggerFactory->get('redis')->debug('Redis command executed', [
                'command' => $command,
                'duration_ms' => round($duration * 1000, 3),
            ]);
        }

        return $result;
    }
}
