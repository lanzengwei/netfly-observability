<?php

declare(strict_types=1);

namespace Netfly\Observability\Tests\Unit;

use Hyperf\Context\Context;
use Netfly\Observability\Support\TraceContext;
use PHPUnit\Framework\TestCase;

class TraceContextTest extends TestCase
{
    protected function tearDown(): void
    {
        Context::destroy('netfly.observability.trace_id');
        Context::destroy('netfly.observability.span_id');
    }

    public function testGenerateTraceId(): void
    {
        $context = new TraceContext();
        $traceId = $context->generateTraceId();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $traceId);
        $this->assertSame($traceId, $context->getTraceId());
    }

    public function testResolveFromHeader(): void
    {
        $context = new TraceContext();
        $incoming = 'abcdef0123456789abcdef0123456789';
        $resolved = $context->resolveFromHeader($incoming);

        $this->assertSame($incoming, $resolved);
        $this->assertSame($incoming, $context->getTraceId());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $context->getSpanId());
    }

    public function testInvalidHeaderGeneratesNewTraceId(): void
    {
        $context = new TraceContext();
        $resolved = $context->resolveFromHeader('invalid');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $resolved);
    }
}
