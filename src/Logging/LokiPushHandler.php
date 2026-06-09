<?php

declare(strict_types=1);

namespace Netfly\Observability\Logging;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Monolog\Handler\AbstractProcessingHandler;

class LokiPushHandler extends AbstractProcessingHandler
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var array<int, array<string, mixed>>
     */
    private $buffer = [];

    public function __construct(
        string $endpoint,
        int $batchSize = 100,
        float $timeout = 2.0,
        $level = \Monolog\Logger::DEBUG,
        bool $bubble = true,
        ?ClientInterface $client = null
    ) {
        parent::__construct($level, $bubble);
        $this->endpoint = $endpoint;
        $this->batchSize = $batchSize;
        $this->client = $client ?? new Client(['timeout' => $timeout]);
    }

    /**
     * @param array<string, mixed>|object $record
     */
    protected function write($record): void
    {
        $normalized = $this->normalize($record);
        $this->buffer[] = $normalized;

        if (count($this->buffer) >= $this->batchSize) {
            $this->flush();
        }
    }

    public function close(): void
    {
        $this->flush();
        parent::close();
    }

    public function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }

        $streams = [];
        foreach ($this->buffer as $entry) {
            $labels = $entry['labels'];
            $key = json_encode($labels);
            if (! isset($streams[$key])) {
                $streams[$key] = [
                    'stream' => $labels,
                    'values' => [],
                ];
            }

            $metadata = [];
            if (! empty($entry['trace_id'])) {
                $metadata['trace_id'] = $entry['trace_id'];
            }
            if (! empty($entry['span_id'])) {
                $metadata['span_id'] = $entry['span_id'];
            }

            $value = [$entry['timestamp'], $entry['line']];
            if ($metadata !== []) {
                $value[] = $metadata;
            }

            $streams[$key]['values'][] = $value;
        }

        $payload = ['streams' => array_values($streams)];

        $this->client->request('POST', $this->endpoint, [
            'json' => $payload,
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        $this->buffer = [];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildEntryFromArray(array $data): array
    {
        return [
            'timestamp' => (string) ((int) (microtime(true) * 1e9)),
            'line' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'labels' => [
                'project' => (string) ($data['project'] ?? 'default'),
                'service' => (string) ($data['service'] ?? 'hyperf'),
                'level' => strtolower((string) ($data['level'] ?? 'info')),
                'channel' => (string) ($data['channel'] ?? 'app'),
            ],
            'trace_id' => (string) ($data['trace_id'] ?? ''),
            'span_id' => (string) ($data['span_id'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed>|object $record
     * @return array<string, mixed>
     */
    private function normalize($record): array
    {
        if (class_exists('Monolog\\LogRecord') && $record instanceof \Monolog\LogRecord) {
            $context = $record->context;
            $data = [
                'timestamp' => $record->datetime->format('Y-m-d\TH:i:s.v\Z'),
                'level' => $record->level->getName(),
                'message' => $record->message,
                'channel' => $record->channel,
                'context' => $context,
                'project' => $context['project'] ?? 'default',
                'service' => $context['service'] ?? 'hyperf',
                'trace_id' => $context['trace_id'] ?? '',
                'span_id' => $context['span_id'] ?? '',
            ];

            return $this->buildEntryFromArray($data);
        }

        $formatted = (string) ($record['formatted'] ?? $record['message'] ?? '');
        $decoded = json_decode($formatted, true);
        if (! is_array($decoded)) {
            $decoded = [
                'message' => $formatted,
                'level' => $record['level_name'] ?? 'INFO',
                'channel' => $record['channel'] ?? 'app',
            ];
        }

        return $this->buildEntryFromArray($decoded);
    }
}
