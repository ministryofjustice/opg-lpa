<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Date\DateService;
use Application\Model\Service\System\Status;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\XmlResponse;
use MakeShared\Constants;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PingHandlerPingdom implements RequestHandlerInterface
{
    public function __construct(
        private readonly Status $statusService,
        private readonly DateService $dateService,
        private string $xmlTemplate = '',
    ) {
        if ($this->xmlTemplate === '') {
            $this->xmlTemplate = "<?xml version='1.0' ?><pingdom_http_custom_check></pingdom_http_custom_check>";
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $start = round(floatval($this->dateService->getNow()->getTimestamp()) * 1000.0);
        $xml = simplexml_load_string($this->xmlTemplate);

        if ($xml === false) {
            throw new RuntimeException('could not marshal XML', 0);
        }

        $result = $this->statusService->check();
        $statusCode = StatusCodeInterface::STATUS_OK;

        if (in_array($result['status'], [Constants::STATUS_PASS, Constants::STATUS_WARN])) {
            $xml->addChild('status', 'OK');
        } else {
            $statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;
            $xml->addChild('status', 'ERROR');
        }

        $end = round(floatval($this->dateService->getNow()->getTimestamp()) * 1000.0);
        $xml->addChild('response_time', strval(($end - $start) / 1000.0));

        $xmlString = $xml->asXML();
        if ($xmlString === false) {
            throw new RuntimeException('could not render XML as string', 0);
        }

        return new XmlResponse($xmlString, $statusCode);
    }
}
