<?php

declare(strict_types=1);

namespace Netfly\Observability\Contract;

interface TraceContextInterface
{
    public function getTraceId(): string;

    public function getSpanId(): string;

    public function setTraceId(string $traceId): void;

    public function setSpanId(string $spanId): void;

    public function generateTraceId(): string;

    public function generateSpanId(): string;

    public function resolveFromHeader(?string $traceId): string;
}
