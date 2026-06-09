<?php

declare(strict_types=1);

namespace Netfly\Observability\Logging;

use Monolog\Formatter\JsonFormatter;

class ObservabilityFormatter extends JsonFormatter
{
    /**
     * @var string
     */
    private $project;

    /**
     * @var string
     */
    private $service;

    /**
     * @var callable
     */
    private $traceIdResolver;

    /**
     * @var callable
     */
    private $spanIdResolver;

    public function __construct(
        string $project,
        string $service,
        callable $traceIdResolver,
        callable $spanIdResolver
    ) {
        parent::__construct(self::BATCH_MODE_NEWLINES, true);
        $this->project = $project;
        $this->service = $service;
        $this->traceIdResolver = $traceIdResolver;
        $this->spanIdResolver = $spanIdResolver;
    }

    /**
     * @param array<string, mixed>|object $record
     * @return string
     */
    public function format($record): string
    {
        $normalized = $this->formatRecordData($record);

        $payload = [
            'timestamp' => $normalized['datetime'] ?? gmdate('Y-m-d\TH:i:s.v\Z'),
            'level' => $normalized['level_name'] ?? 'INFO',
            'message' => $normalized['message'] ?? '',
            'project' => $this->project,
            'service' => $this->service,
            'trace_id' => ($this->traceIdResolver)(),
            'span_id' => ($this->spanIdResolver)(),
            'channel' => $normalized['channel'] ?? 'app',
            'context' => $normalized['context'] ?? [],
        ];

        return $this->toJson($payload) . "\n";
    }

    /**
     * @param array<string, mixed>|object $record
     * @return array<string, mixed>
     */
    private function formatRecordData($record): array
    {
        if (class_exists('Monolog\\LogRecord') && $record instanceof \Monolog\LogRecord) {
            return [
                'datetime' => $record->datetime->format('Y-m-d\TH:i:s.v\Z'),
                'level_name' => $record->level->getName(),
                'message' => $record->message,
                'channel' => $record->channel,
                'context' => $record->context,
            ];
        }

        if (is_array($record)) {
            $datetime = $record['datetime'] ?? null;
            if ($datetime instanceof \DateTimeInterface) {
                $record['datetime'] = $datetime->format('Y-m-d\TH:i:s.v\Z');
            }

            return $record;
        }

        return [];
    }
}
