<?php

declare(strict_types=1);

namespace Netfly\Observability\Tests\Unit;

use Netfly\Observability\Logging\ObservabilityFormatter;
use PHPUnit\Framework\TestCase;

class ObservabilityFormatterTest extends TestCase
{
    public function testJsonContainsRequiredFields(): void
    {
        $formatter = new ObservabilityFormatter(
            'order-service',
            'api',
            function (): string {
                return 'abc123def4567890abc123def4567890';
            },
            function (): string {
                return 'span1234567890ab';
            }
        );

        $output = $formatter->format([
            'datetime' => new \DateTimeImmutable('2026-06-09T10:00:00.123Z'),
            'level_name' => 'INFO',
            'message' => 'HTTP request completed',
            'channel' => 'http',
            'context' => ['status' => 200],
        ]);

        $decoded = json_decode(trim($output), true);
        $this->assertIsArray($decoded);
        $this->assertSame('order-service', $decoded['project']);
        $this->assertSame('api', $decoded['service']);
        $this->assertSame('abc123def4567890abc123def4567890', $decoded['trace_id']);
        $this->assertSame('span1234567890ab', $decoded['span_id']);
        $this->assertSame('http', $decoded['channel']);
        $this->assertSame(200, $decoded['context']['status']);
    }
}
