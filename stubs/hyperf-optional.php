<?php

declare(strict_types=1);

namespace Hyperf\Database\Events;

class QueryExecuted
{
    public string $sql = '';

    public ?string $connectionName = 'default';

    public float $time = 0.0;
}

namespace Hyperf\Redis\Event;

class CommandExecuted
{
    public string $command = '';

    public float $time = 0.0;
}

namespace Hyperf\Redis;

class Redis
{
}

namespace Hyperf\Amqp\Event;

class BeforeConsume
{
    public function getMessage(): object
    {
        return new class() {
            public function getQueue(): string
            {
                return '';
            }
        };
    }
}

class AfterConsume
{
    public function getMessage(): object
    {
        return new class() {
            public function getQueue(): string
            {
                return '';
            }
        };
    }
}

class FailToConsume
{
    public function getMessage(): object
    {
        return new class() {
            public function getQueue(): string
            {
                return '';
            }
        };
    }
}

class AfterProduce
{
    public function getMessage(): object
    {
        return new class() {
            public function getExchange(): string
            {
                return '';
            }

            public function getRoutingKey(): string
            {
                return '';
            }
        };
    }
}
