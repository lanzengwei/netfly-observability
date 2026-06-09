<?php

declare(strict_types=1);

namespace Netfly\Observability\Middleware;

use Hyperf\HttpServer\Contract\RequestInterface;
use Netfly\Observability\Contract\FeatureSwitchInterface;
use Netfly\Observability\Contract\TraceContextInterface;
use Netfly\Observability\Logging\ObservabilityLoggerFactory;
use Netfly\Observability\Metric\HttpMetricCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpObservabilityMiddleware implements MiddlewareInterface
{
    /**
     * @var FeatureSwitchInterface
     */
    private $featureSwitch;

    /**
     * @var TraceContextInterface
     */
    private $traceContext;

    /**
     * @var HttpMetricCollector
     */
    private $collector;

    /**
     * @var ObservabilityLoggerFactory
     */
    private $loggerFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        FeatureSwitchInterface $featureSwitch,
        TraceContextInterface $traceContext,
        HttpMetricCollector $collector,
        ObservabilityLoggerFactory $loggerFactory,
        RequestInterface $request
    ) {
        $this->featureSwitch = $featureSwitch;
        $this->traceContext = $traceContext;
        $this->collector = $collector;
        $this->loggerFactory = $loggerFactory;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->featureSwitch->isModuleEnabled('http')) {
            return $handler->handle($request);
        }

        $traceId = $this->traceContext->resolveFromHeader(
            $request->getHeaderLine('X-Trace-Id') ?: null
        );

        $start = microtime(true);
        $response = $handler->handle($request);
        $duration = microtime(true) - $start;

        $method = $request->getMethod();
        $route = $this->resolveRoute();
        $status = $response->getStatusCode();

        $this->collector->record($method, $route, $status, $duration);

        if ($this->featureSwitch->isModuleEnabled('logging')) {
            $this->loggerFactory->get('http')->info('HTTP request completed', [
                'method' => $method,
                'path' => $this->request->getUri()->getPath(),
                'route' => $route,
                'status' => $status,
                'duration_ms' => round($duration * 1000, 3),
            ]);
        }

        return $response->withHeader('X-Trace-Id', $traceId);
    }

    private function resolveRoute(): string
    {
        $dispatched = $this->request->getAttribute(\Hyperf\HttpServer\Router\Dispatched::class);
        if ($dispatched && $dispatched->isFound()) {
            $handler = $dispatched->handler;
            if (is_string($handler)) {
                return $handler;
            }
            if (is_array($handler) && isset($handler[0], $handler[1])) {
                return sprintf('%s@%s', $handler[0], $handler[1]);
            }
        }

        return $this->request->getUri()->getPath();
    }
}
