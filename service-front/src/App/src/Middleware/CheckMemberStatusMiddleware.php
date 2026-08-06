<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Authentication\AuthenticationService;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckMemberStatusMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly SharedSpaceService $sharedSpaceService,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $request->getAttribute(RequestAttribute::IDENTITY);
        $sharedSpaceId = $identity instanceof User ? ($identity->getSharedSpaceId() ?? null) : null;

        if ($sharedSpaceId !== null) {
            $memberDetails = $this->sharedSpaceService->getMember($identity->id());

            if (is_array($memberDetails) && !$memberDetails['isActive']) {
                $this->authenticationService->clearIdentity();

                $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
                if ($session instanceof SessionInterface) {
                    $session->clear();
                    $session->regenerate();
                }

                return new RedirectResponse(
                    $this->urlHelper->generate('application.login', ['state' => 'member-suspended'])
                );
            }
        }

        return $handler->handle($request);
    }
}
