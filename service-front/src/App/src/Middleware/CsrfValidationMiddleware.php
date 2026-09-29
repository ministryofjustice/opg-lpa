<?php

declare(strict_types=1);

namespace App\Middleware;

use App\View\Twig\FlashMessenger;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class CsrfValidationMiddleware implements MiddlewareInterface
{
    public const string TOKEN_ATTRIBUTE = 'csrfToken';

    private const string CSRF_KEY = '__csrf';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(RouteResult::class);
        if ($route instanceof RouteResult) {
            $matchedRoute = $route->getMatchedRoute();
            $routeOptions = $matchedRoute !== false ? ($matchedRoute->getOptions() ?: []) : [];
            if (($routeOptions['csrf'] ?? false) === false && ($routeOptions['unauthenticated_route'] ?? false) === true) {
                return $handler->handle($request);
            }
        }

        /** @var SessionInterface|null $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            // JSON requests cannot be forged cross-origin by a browser (the SOP blocks
            // non-simple content-types without a preflight), so CSRF validation is not
            // applicable.  Skip token checking for application/json requests.
            $contentType = $request->getHeaderLine('Content-Type');
            $isJson      = str_contains(strtolower($contentType), 'application/json');

            if (!$isJson) {
                $postToken    = ($request->getParsedBody() ?? [])[self::CSRF_KEY] ?? '';
                $sessionToken = ($session instanceof SessionInterface) ? $session->get(self::CSRF_KEY, '') : '';

                // Validate without consuming: compare directly so that concurrent XHR requests
                // on the same page (popup opens, reuse-details, etc.) don't invalidate the token
                // embedded in the parent page's main form.
                if ($postToken === '' || $postToken !== $sessionToken) {
                    $this->logger->warning('csrf.validation_failed', [
                        'reason'              => $this->failureReason($postToken, $sessionToken),
                        'path'                => $request->getUri()->getPath(),
                        'session_cookie_sent' => array_key_exists(session_name(), $request->getCookieParams()),
                    ]);

                    $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                    $flash->flash(FlashMessenger::ERROR, ['Invalid CSRF token. Please try submitting the form again.']);
                    return new RedirectResponse($request->getUri()->getPath());
                }
            }
        }

        // Reuse the existing session token if one is present; only generate a new token
        // when none exists (e.g. fresh session, or first request after login).
        // This prevents XHR sub-requests from overwriting the token that was embedded in
        // the page's main form during the initial full-page load.
        if ($session instanceof SessionInterface && $session->has(self::CSRF_KEY)) {
            $token = $session->get(self::CSRF_KEY);
        } else {
            $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
            $token = $guard->generateToken();
        }

        $request = $request->withAttribute(self::TOKEN_ATTRIBUTE, $token);

        return $handler->handle($request);
    }

    /**
     * Says which side of the comparison was missing or wrong, without logging either token.
     */
    private function failureReason(mixed $postToken, mixed $sessionToken): string
    {
        return match (true) {
            $postToken === ''    => 'no_post_token',
            $sessionToken === '' => 'no_session_token',
            default              => 'mismatch',
        };
    }
}
