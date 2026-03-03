<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Laminas\Diactoros\Response\JsonResponse;
use MakeShared\Logging\LoggerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PostcodeHandler implements RequestHandlerInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public function __construct(
        private readonly OrdnanceSurvey $addressLookup,
        LoggerInterface $logger,
    ) {
        $this->setLogger($logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $postcode = $queryParams['postcode'] ?? null;

        if (empty($postcode) || !is_string($postcode)) {
            return new JsonResponse([
                'isPostcodeValid' => false,
                'success'         => false,
                'addresses'       => [],
            ], 200);
        }

        $addresses = [];

        try {
            $addresses = $this->addressLookup->lookupPostcode($postcode);
        } catch (RuntimeException $e) {
            $this->getLogger()->warning('Exception from postcode lookup', [
                'exception' => $e,
            ]);
        }

        return new JsonResponse([
            'isPostcodeValid' => true,
            'success'         => (count($addresses) > 0),
            'addresses'       => $addresses,
        ]);
    }
}
