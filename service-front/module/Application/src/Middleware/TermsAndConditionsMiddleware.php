<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use DateTime;
use Exception;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Checks whether the terms and conditions have changed since the user last logged in.
 * A session flag records whether the user has already been shown the 'Terms have changed'
 * page in the current session; if they haven't, they are redirected to it.
 */
class TermsAndConditionsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly array $config,
        private readonly SessionUtility $sessionUtility,
        private readonly AuthenticationService $authenticationService,
        private readonly ?UrlHelper $urlHelper = null,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $hasSeenTerms = $this->checkTermsAndConditions();

        if ($hasSeenTerms) {
            return $handler->handle($request);
        }

        // TODO(mezzio): update routeName when we setup Mezzio routes
        $uri = $this->urlHelper->generate('user/dashboard/terms-changed');
        return new RedirectResponse($uri);
    }

    private function checkTermsAndConditions(): bool
    {
        $identity = $this->authenticationService->getIdentity();

        if ($identity === null) {
            return true;
        }

        try {
            $termsLastUpdated = new DateTime($this->config['terms']['lastUpdated']);
        } catch (Exception $_) {
            return true;
        }

        if ($identity->lastLogin() < $termsLastUpdated) {
            $seen = $this->sessionUtility->getFromMvc(
                ContainerNamespace::TERMS_AND_CONDITIONS_CHECK,
                'seen'
            );

            if ($seen === null) {
                $this->sessionUtility->setInMvc(
                    ContainerNamespace::TERMS_AND_CONDITIONS_CHECK,
                    'seen',
                    true
                );

                return false;
            }
        }

        return true;
    }
}
