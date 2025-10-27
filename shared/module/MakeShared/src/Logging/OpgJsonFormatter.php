<?php

declare(strict_types=1);

namespace MakeShared\Logging;

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

        $record = [
            'time' => $original['datetime'],
            'level' => $original['level_name'],
            'msg' => $original['message'],
            'service_name' => $original['channel'],
        ];

        if (isset($original['extra']['trace_id'])) {
            $record['trace_id'] = $original['extra']['trace_id'];
            unset($original['extra']['trace_id']);
        }

        if ($this->requestMethod !== null || $this->requestPath !== null) {
            $record['request'] = [
                'method' => $this->requestMethod,
                'path' => $this->requestPath,
            ];
        }

        unset($original['datetime']);
        unset($original['level_name']);
        unset($original['message']);
        unset($original['channel']);

        return $this->toJson(array_filter($record + $original)) . "\n";
    }
}
