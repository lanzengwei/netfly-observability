<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Netfly\Observability\Logging\ObservabilityLoggerFactory;
use Netfly\Observability\Metric\AmqpMetricCollector;
use Netfly\Observability\Metric\DbMetricCollector;
use Netfly\Observability\Metric\RedisMetricCollector;

class DemoController
{
    /**
     * @var DbMetricCollector
     */
    private $dbCollector;

    /**
     * @var RedisMetricCollector
     */
    private $redisCollector;

    /**
     * @var AmqpMetricCollector
     */
    private $amqpCollector;

    /**
     * @var ObservabilityLoggerFactory
     */
    private $loggerFactory;

    public function __construct(
        DbMetricCollector $dbCollector,
        RedisMetricCollector $redisCollector,
        AmqpMetricCollector $amqpCollector,
        ObservabilityLoggerFactory $loggerFactory
    ) {
        $this->dbCollector = $dbCollector;
        $this->redisCollector = $redisCollector;
        $this->amqpCollector = $amqpCollector;
        $this->loggerFactory = $loggerFactory;
    }

    public function demo(ResponseInterface $response)
    {
        $this->dbCollector->record('default', 'SELECT', 0.012);
        $this->redisCollector->record('GET', 0.003);
        $this->amqpCollector->recordPublished('demo.exchange', 'demo.key');
        $this->amqpCollector->recordConsumed('demo.queue', 'success', 0.008);

        $this->loggerFactory->get('mysql')->info('Demo MySQL query');
        $this->loggerFactory->get('redis')->info('Demo Redis command');
        $this->loggerFactory->get('rabbitmq')->info('Demo RabbitMQ message');

        return $response->json([
            'status' => 'ok',
            'message' => 'demo metrics and logs recorded',
        ]);
    }
}
