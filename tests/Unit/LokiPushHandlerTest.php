<?php

declare(strict_types=1);

namespace Netfly\Observability\Tests\Unit;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Monolog\Level;
use Monolog\LogRecord;
use Netfly\Observability\Logging\LokiPushHandler;
use PHPUnit\Framework\TestCase;

class LokiPushHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testBuildEntryFromArray(): void
    {
        $handler = new LokiPushHandler('http://loki:3100/loki/api/v1/push', 1, 1.0);
        $entry = $handler->buildEntryFromArray([
            'project' => 'demo',
            'service' => 'api',
            'level' => 'info',
            'channel' => 'http',
            'trace_id' => 'trace123',
            'span_id' => 'span456',
            'message' => 'ok',
        ]);

        $this->assertSame('demo', $entry['labels']['project']);
        $this->assertSame('trace123', $entry['trace_id']);
        $this->assertStringContainsString('ok', $entry['line']);
    }

    public function testFlushSendsPayload(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('POST', 'http://loki:3100/loki/api/v1/push', Mockery::type('array'))
            ->andReturn(new Response(204));

        $handler = new LokiPushHandler(
            'http://loki:3100/loki/api/v1/push',
            1,
            1.0,
            \Monolog\Logger::DEBUG,
            true,
            $client
        );

        $handler->handle(new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'http',
            level: Level::Info,
            message: 'test',
            context: [
                'project' => 'demo',
                'service' => 'api',
                'trace_id' => 'trace123',
            ],
            extra: [],
        ));

        $this->addToAssertionCount(1);
    }
}
