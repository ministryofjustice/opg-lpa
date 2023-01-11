<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use Socket;
use MakeShared\Telemetry\Segment;

class XrayExporter implements ExporterInterface
{
    protected const MAX_PAYLOAD_LEN = 64000;

    public Socket|false $socket;

    public function __construct(
        private string $host = 'localhost',
        private int $port = 2000
    ) {
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (!$this->socket) {
            trigger_error(sprintf(
                'Error creating X-ray socket (%s:%d): %s',
                $host,
                $port,
                socket_strerror(socket_last_error()),
            ), E_USER_WARNING);
        }
    }

    public function __destruct()
    {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }

    public function export(Segment $segment): void
    {
        if (!$this->socket) {
            return;
        }

        $payload = $this->serialiseSegment($segment);

        $length = strlen($payload);

        if ($length < $this::MAX_PAYLOAD_LEN || empty($segment->children)) {
            $this->sendPayload($payload);

            return;
        }

        $parentSegment = clone $segment;
        $parentSegment->children = [];
        $this->sendPayload($this->serialiseSegment($parentSegment));

        foreach ($segment->children as $childSegment) {
            $childSegment->isIndependent = true;
            $childSegment->parentId = $segment->id;
            $childSegment->traceId = $segment->traceId;
            $this->export($childSegment);
        }
    }

    private function serialiseSegment(Segment $segment): string
    {
        return json_encode(['format' => 'json', 'version' => 1]) . "\n" . json_encode($segment);
    }

    protected function sendPayload(string $payload): void
    {
        if (!$this->socket) {
            return;
        }

        socket_sendto($this->socket, $payload, strlen($payload), 0, $this->host, $this->port);
    }
}
