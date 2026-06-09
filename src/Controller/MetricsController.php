<?php

declare(strict_types=1);

namespace Netfly\Observability\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Netfly\Observability\Metric\MetricRegistry;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\ResponseInterface;

#[Controller]
class MetricsController
{
    /**
     * @var MetricRegistry
     */
    private $metricRegistry;

    /**
     * @var HttpResponse
     */
    private $response;

    public function __construct(MetricRegistry $metricRegistry, HttpResponse $response)
    {
        $this->metricRegistry = $metricRegistry;
        $this->response = $response;
    }

    #[GetMapping(path: '/metrics')]
    public function metrics(): ResponseInterface
    {
        $registry = $this->metricRegistry->getRegistry();
        $renderer = new RenderTextFormat();
        $content = $renderer->render($registry->getMetricFamilySamples());

        return $this->response->raw($content)
            ->withHeader('Content-Type', RenderTextFormat::MIME_TYPE);
    }
}
