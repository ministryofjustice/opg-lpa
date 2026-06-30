<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Authentication\AuthenticationService;
use DateTime;
use Exception;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Checks whether the terms and conditions have changed since the user last logged in.
 * A session flag records whether the user has already been shown the 'Terms have changed'
 * page in the current session; if they haven't, they are redirected to it once.
 */
class TermsAndConditionsMiddleware implements MiddlewareInterface
{
    private const string TERMS_SEEN_SESSION_KEY = 'termsAndConditionsCheckSeen';
    private const string TERMS_CHANGED_ROUTE = 'user/dashboard/terms-changed';

    public function __construct(
        private readonly array $config,
        private readonly AuthenticationService $authenticationService,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Avoid redirect loop on the terms-changed page itself
        $routeResult = $request->getAttribute(RouteResult::class);
        if (
            $routeResult instanceof RouteResult
            && $routeResult->getMatchedRouteName() === self::TERMS_CHANGED_ROUTE
        ) {
            return $handler->handle($request);
        }

        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$this->hasSeenTerms($session)) {
            return new RedirectResponse($this->urlHelper->generate(self::TERMS_CHANGED_ROUTE));
        }

        return $handler->handle($request);
    }

    private function hasSeenTerms(mixed $session): bool
    {
        $identity = $this->authenticationService->getIdentity();

        if ($identity === null) {
            return true;
        }

        try {
            $lastUpdated = $this->config['terms']['lastUpdated'] ?? null;
            if ($lastUpdated === null) {
                return true;
            }
            $termsLastUpdated = new DateTime($lastUpdated);
        } catch (Exception) {
            return true;
        }

        if ($identity->lastLogin() < $termsLastUpdated) {
            if ($session === null || !$session->has(self::TERMS_SEEN_SESSION_KEY)) {
                $session?->set(self::TERMS_SEEN_SESSION_KEY, true);
                return false;
            }
        }

        return true;
    }
}
