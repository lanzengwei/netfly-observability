<?php

declare(strict_types=1);

namespace Netfly\Observability\Tests\Unit;

use Hyperf\Contract\ConfigInterface;
use Netfly\Observability\Metric\DbMetricCollector;
use Netfly\Observability\Metric\HttpMetricCollector;
use Netfly\Observability\Metric\MetricRegistry;
use Netfly\Observability\Support\ProjectContext;
use PHPUnit\Framework\TestCase;
use Prometheus\RenderTextFormat;

class MetricRegistryTest extends TestCase
{
    public function testHttpMetricsContainProjectLabel(): void
    {
        $registry = $this->makeRegistry();
        $collector = new HttpMetricCollector($registry);
        $collector->record('GET', '/users', 200, 0.12);

        $output = (new RenderTextFormat())->render($registry->getRegistry()->getMetricFamilySamples());
        $this->assertStringContainsString('netfly_http_requests_total', $output);
        $this->assertStringContainsString('project="demo"', $output);
    }

    public function testDbMetricsRecorded(): void
    {
        $registry = $this->makeRegistry();
        $collector = new DbMetricCollector($registry);
        $collector->record('default', 'SELECT', 0.05);

        $output = (new RenderTextFormat())->render($registry->getRegistry()->getMetricFamilySamples());
        $this->assertStringContainsString('netfly_db_queries_total', $output);
    }

    private function makeRegistry(): MetricRegistry
    {
        $config = new class () implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                if ($key === 'observability.metrics.namespace') {
                    return 'netfly';
                }
                if ($key === 'observability.project') {
                    return 'demo';
                }
                if ($key === 'observability.service') {
                    return 'api';
                }

                return $default;
            }

            public function has(string $key): bool
            {
                return true;
            }

            public function set(string $key, $value): void
            {
            }
        };

        return new MetricRegistry($config, new ProjectContext($config));
    }
}
