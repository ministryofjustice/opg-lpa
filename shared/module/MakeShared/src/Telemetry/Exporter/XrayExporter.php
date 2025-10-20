<?php

declare(strict_types=1);

namespace MakeShared\Telemetry\Exporter;

use MakeShared\Logging\LoggerTrait;
use MakeShared\Telemetry\Segment;
use json_encode;
use Psr\Log\LoggerAwareInterface;
use Socket;
use socket_create;
use socket_close;
use socket_last_error;
use socket_sendto;
use socket_strerror;
use sprintf;
use strlen;
use trigger_error;

class XrayExporter implements ExporterInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public const MAX_PAYLOAD_LEN = 64000;

    public Socket|false $socket;

    public function __construct(
        private string $host = 'localhost',
        private int $port = 2000,
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
        if (!$this->socket || !$segment->sampled) {
            return;
        }

        $payload = json_encode(['format' => 'json', 'version' => 1]) . "\n" . json_encode($segment);

        if (strlen($payload) > $this::MAX_PAYLOAD_LEN) {
            $this->getLogger()->error("Segment too large to export: " . substr($payload, 0, 400));
            return;
        }

        $result = socket_sendto($this->socket, $payload, strlen($payload), 0, $this->host, $this->port);

        if ($result === false) {
            $this->getLogger()->error("Unable to send telemetry to {$this->host}:{$this->port}");
        }
    }
}
