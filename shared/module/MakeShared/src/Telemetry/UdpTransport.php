<?php

namespace MakeShared\Telemetry;

use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Common\Future\CompletedFuture;
use OpenTelemetry\SDK\Common\Future\ErrorFuture;
use OpenTelemetry\SDK\Common\Future\FutureInterface;
use explode;
use RuntimeException;
use Socket;
use socket_close;
use socket_create;
use socket_last_error;
use socket_sendto;
use socket_strerror;
use trigger_error;

class UdpTransport implements TransportInterface
{
    private string $host;
    private int $port;
    private ?Socket $socket = null;

    /**
     * @param string $url host:port, usually localhost:2000
     */
    public function __construct(string $url)
    {
        $urlParts = explode(':', $url);
        $this->host = $urlParts[0];
        $this->port = intval($urlParts[1]);

        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (!$this->socket) {
            trigger_error(
                sprintf(
                    'Error creating X-ray socket (%s:%d): %s',
                    $this->host,
                    $this->port,
                    socket_strerror(socket_last_error()),
                ),
                E_USER_WARNING
            );
        }
    }

    public function contentType(): string
    {
        return 'application/json';
    }

    public function send(string $payload, ?CancellationInterface $cancellation = null): FutureInterface
    {
        if (is_null($this->socket)) {
            return new ErrorFuture(new RuntimeException('socket not open'));
        }

        socket_sendto($this->socket, $payload, strlen($payload), 0, $this->host, $this->port);

        return new CompletedFuture(0);
    }

    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        if (!is_null($this->socket)) {
            socket_close($this->socket);
        }

        return true;
    }

    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        return true;
    }
}
