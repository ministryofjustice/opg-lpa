<?php

declare(strict_types=1);

namespace MakeShared\Logging;

use MakeShared\Constants;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

class OpgJsonFormatter extends NormalizerFormatter
{
    public ?string $requestMethod = null;
    public ?string $requestPath = null;

    /**
     * {@inheritDoc}
     */
    public function format(LogRecord $record): string
    {
        $original = parent::format($record);
        $timestampMs = (int) $record->datetime->format('Uv');

        $record = [
            'time' => $original['datetime'],
            'level' => $original['level_name'],
            'msg' => $original['message'],
            'service_name' => $original['channel'],
            Constants::TRACE_ID_FIELD_NAME => $original['extra'][Constants::TRACE_ID_FIELD_NAME] ?? 'missing-trace-id',
        ];

        if (isset($original['extra'][Constants::TRACE_ID_FIELD_NAME])) {
            unset($original['extra'][Constants::TRACE_ID_FIELD_NAME]);
        }

        if ($this->requestMethod !== null || $this->requestPath !== null) {
            $record['request'] = [
                'method' => $this->requestMethod,
                'path' => $this->requestPath,
            ];
        }

        $event = $original['context']['event'] ?? null;
        if (MetricsLogger::isEvent($event)) {
            $dimensions = $original['context'][MetricsLogger::CONTEXT_DIMENSIONS_KEY] ?? [];
            unset($original['context'][MetricsLogger::CONTEXT_DIMENSIONS_KEY]);

            $record += MetricsLogger::emfFields($event, $dimensions, $timestampMs);
        }

        unset($original['datetime']);
        unset($original['level_name']);
        unset($original['message']);
        unset($original['channel']);

        return $this->toJson(array_filter($record + $original)) . "\n";
    }
}
