<?php

declare(strict_types=1);

namespace Netfly\Observability\Logging;

use Hyperf\Contract\ConfigInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Netfly\Observability\Contract\FeatureSwitchInterface;
use Netfly\Observability\Contract\ProjectContextInterface;
use Netfly\Observability\Contract\TraceContextInterface;
use Psr\Log\LoggerInterface;

class ObservabilityLoggerFactory
{
    /**
     * @var array<string, LoggerInterface>
     */
    private $loggers = [];

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var FeatureSwitchInterface
     */
    private $featureSwitch;

    /**
     * @var ProjectContextInterface
     */
    private $projectContext;

    /**
     * @var TraceContextInterface
     */
    private $traceContext;

    public function __construct(
        ConfigInterface $config,
        FeatureSwitchInterface $featureSwitch,
        ProjectContextInterface $projectContext,
        TraceContextInterface $traceContext
    ) {
        $this->config = $config;
        $this->featureSwitch = $featureSwitch;
        $this->projectContext = $projectContext;
        $this->traceContext = $traceContext;
    }

    public function get(string $channel = 'app'): LoggerInterface
    {
        if (isset($this->loggers[$channel])) {
            return $this->loggers[$channel];
        }

        $logger = new Logger($channel);
        $formatter = new ObservabilityFormatter(
            $this->projectContext->getProject(),
            $this->projectContext->getService(),
            function (): string {
                return $this->traceContext->getTraceId();
            },
            function (): string {
                return $this->traceContext->getSpanId();
            }
        );

        $driver = (string) $this->config->get('observability.logging.driver', 'stdout');

        if ($driver === 'loki') {
            $lokiConfig = $this->config->get('observability.logging.loki', []);
            $handler = new LokiPushHandler(
                (string) ($lokiConfig['endpoint'] ?? 'http://loki:3100/loki/api/v1/push'),
                (int) ($lokiConfig['batch_size'] ?? 100),
                (float) ($lokiConfig['timeout'] ?? 2.0)
            );
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
        } else {
            $target = $driver === 'file'
                ? (string) $this->config->get('observability.logging.file', 'php://stdout')
                : 'php://stdout';

            $handler = new StreamHandler($target, Logger::DEBUG);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
        }

        $this->loggers[$channel] = $logger;

        return $logger;
    }

    public function isLoggingEnabled(): bool
    {
        return $this->featureSwitch->isModuleEnabled('logging');
    }
}
