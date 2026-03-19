<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionSetExpiryHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return new TextResponse('Method not allowed', 405);
        }

        $body = $request->getBody();
        $body->rewind();
        $content = $body->getContents();

        $expireInSeconds = null;

        if ($content !== '') {
            $decodedContent = json_decode($content, true);

            if (is_array($decodedContent) && array_key_exists('expireInSeconds', $decodedContent)) {
                $expireInSeconds = $decodedContent['expireInSeconds'];
            }
        }

        if ($expireInSeconds === null) {
            return new TextResponse('Malformed request', 400);
        }

        $remainingSeconds = $this->authenticationService->setSessionExpiry(intval($expireInSeconds));

        return new JsonResponse(['remainingSeconds' => $remainingSeconds]);
    }
}
