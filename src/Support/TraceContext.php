<?php

declare(strict_types=1);

namespace Netfly\Observability\Support;

use Hyperf\Context\Context;
use Netfly\Observability\Contract\TraceContextInterface;

class TraceContext implements TraceContextInterface
{
    private const TRACE_KEY = 'netfly.observability.trace_id';

    private const SPAN_KEY = 'netfly.observability.span_id';

    public function getTraceId(): string
    {
        $traceId = Context::get(self::TRACE_KEY);
        if (is_string($traceId) && $traceId !== '') {
            return $traceId;
        }

        return $this->generateTraceId();
    }

    public function getSpanId(): string
    {
        $spanId = Context::get(self::SPAN_KEY);
        if (is_string($spanId) && $spanId !== '') {
            return $spanId;
        }

        return $this->generateSpanId();
    }

    public function setTraceId(string $traceId): void
    {
        Context::set(self::TRACE_KEY, $traceId);
    }

    public function setSpanId(string $spanId): void
    {
        Context::set(self::SPAN_KEY, $spanId);
    }

    public function generateTraceId(): string
    {
        $traceId = bin2hex(random_bytes(16));
        $this->setTraceId($traceId);

        return $traceId;
    }

    public function generateSpanId(): string
    {
        $spanId = bin2hex(random_bytes(8));
        $this->setSpanId($spanId);

        return $spanId;
    }

    public function resolveFromHeader(?string $traceId): string
    {
        if (is_string($traceId) && preg_match('/^[a-f0-9]{32}$/i', $traceId)) {
            $normalized = strtolower($traceId);
            $this->setTraceId($normalized);
            $this->generateSpanId();

            return $normalized;
        }

        return $this->generateTraceId();
    }
}
