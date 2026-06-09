<?php

declare(strict_types=1);

namespace Netfly\Observability\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Netfly\Observability\Contract\FeatureSwitchInterface;
use Netfly\Observability\Logging\ObservabilityLoggerFactory;
use Netfly\Observability\Metric\AmqpMetricCollector;

class AmqpListener implements ListenerInterface
{
    /**
     * @var FeatureSwitchInterface
     */
    private $featureSwitch;

    /**
     * @var AmqpMetricCollector
     */
    private $collector;

    /**
     * @var ObservabilityLoggerFactory
     */
    private $loggerFactory;

    /**
     * @var array<string, float>
     */
    private $consumeStartTimes = [];

    public function __construct(
        FeatureSwitchInterface $featureSwitch,
        AmqpMetricCollector $collector,
        ObservabilityLoggerFactory $loggerFactory
    ) {
        $this->featureSwitch = $featureSwitch;
        $this->collector = $collector;
        $this->loggerFactory = $loggerFactory;
    }

    public function listen(): array
    {
        $events = [];

        if (class_exists('Hyperf\\Amqp\\Event\\BeforeConsume')) {
            $events[] = 'Hyperf\\Amqp\\Event\\BeforeConsume';
        }
        if (class_exists('Hyperf\\Amqp\\Event\\AfterConsume')) {
            $events[] = 'Hyperf\\Amqp\\Event\\AfterConsume';
        }
        if (class_exists('Hyperf\\Amqp\\Event\\FailToConsume')) {
            $events[] = 'Hyperf\\Amqp\\Event\\FailToConsume';
        }
        if (class_exists('Hyperf\\Amqp\\Event\\AfterProduce')) {
            $events[] = 'Hyperf\\Amqp\\Event\\AfterProduce';
        }
        if (class_exists('Hyperf\\Amqp\\Event\\FailToProduce')) {
            $events[] = 'Hyperf\\Amqp\\Event\\FailToProduce';
        }

        return $events;
    }

    public function process(object $event): void
    {
        if (! $this->featureSwitch->isModuleEnabled('rabbitmq')) {
            return;
        }

        if (class_exists('Hyperf\\Amqp\\Event\\BeforeConsume') && $event instanceof \Hyperf\Amqp\Event\BeforeConsume) {
            $queue = $event->getMessage()->getQueue();
            $this->consumeStartTimes[$queue] = microtime(true);

            return;
        }

        if (class_exists('Hyperf\\Amqp\\Event\\AfterProduce') && $event instanceof \Hyperf\Amqp\Event\AfterProduce) {
            $message = $event->getMessage();
            $this->collector->recordPublished(
                $message->getExchange(),
                $message->getRoutingKey()
            );

            if ($this->featureSwitch->isModuleEnabled('logging')) {
                $this->loggerFactory->get('rabbitmq')->info('AMQP message published', [
                    'exchange' => $message->getExchange(),
                    'routing_key' => $message->getRoutingKey(),
                ]);
            }

            return;
        }

        if (class_exists('Hyperf\\Amqp\\Event\\AfterConsume') && $event instanceof \Hyperf\Amqp\Event\AfterConsume) {
            $this->recordConsume($event->getMessage()->getQueue(), 'success');

            return;
        }

        if (class_exists('Hyperf\\Amqp\\Event\\FailToConsume') && $event instanceof \Hyperf\Amqp\Event\FailToConsume) {
            $this->recordConsume($event->getMessage()->getQueue(), 'fail');

            return;
        }

        if (class_exists('Hyperf\\Amqp\\Event\\FailToProduce') && $event instanceof \Hyperf\Amqp\Event\FailToProduce) {
            $message = $event->getMessage();
            $this->collector->recordPublished(
                $message->getExchange(),
                $message->getRoutingKey() . ':fail'
            );

            if ($this->featureSwitch->isModuleEnabled('logging')) {
                $this->loggerFactory->get('rabbitmq')->error('AMQP message publish failed', [
                    'exchange' => $message->getExchange(),
                    'routing_key' => $message->getRoutingKey(),
                ]);
            }
        }
    }

    private function recordConsume(string $queue, string $result): void
    {
        $start = $this->consumeStartTimes[$queue] ?? microtime(true);
        $duration = microtime(true) - $start;
        unset($this->consumeStartTimes[$queue]);

        $this->collector->recordConsumed($queue, $result, $duration);

        if ($this->featureSwitch->isModuleEnabled('logging')) {
            $this->loggerFactory->get('rabbitmq')->info('AMQP message consumed', [
                'queue' => $queue,
                'result' => $result,
                'duration_ms' => round($duration * 1000, 3),
            ]);
        }
    }
}
