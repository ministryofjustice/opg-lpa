<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\OneLogin\OneLoginService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Publishes our public keys as a JSON Web Key Set, so GOV.UK One Login can verify the
 * client assertions and request objects we sign, and we can rotate keys without asking
 * One Login to change our configuration.
 *
 * https://docs.sign-in.service.gov.uk/before-integrating/set-up-your-public-and-private-keys/#share-your-public-keys-using-a-jwks-endpoint
 */
class OneLoginJwksHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly OneLoginService $oneLoginService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($this->oneLoginService->jwks());
    }
}
