<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use DateTime;
use Exception;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as MVCResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Checks whether the terms and conditions have changed since the user last logged in.
 * A session flag records whether the user has already been shown the 'Terms have changed'
 * page in the current session; if they haven't, they are redirected to it.
 *
 * Implements both the laminas-mvc listener interface (via listen()) and the PSR-7
 * MiddlewareInterface (via process()), so it can run in a laminas-mvc PipeSpec during
 * the Mezzio migration as well as in a full Mezzio pipeline.
 */
class TermsAndConditionsListener extends AbstractListenerAggregate implements MiddlewareInterface
{
    public function __construct(
        private readonly array $config,
        private readonly SessionUtility $sessionUtility,
        private readonly AuthenticationService $authenticationService,
        private readonly ?UrlHelper $urlHelper = null,
    ) {
    }

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'listen'],
            $priority
        );
    }

    public function listen(MvcEvent $event): ?MVCResponse
    {
        $hasSeenTerms = $this->checkTermsAndConditions();

        if ($hasSeenTerms) {
            return null;
        }

        $router = $event->getRouter();
        $url = $router->assemble([], ['name' => 'user/dashboard/terms-changed']);

        $response = new Response();
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(302);

        return $response;
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

        $termsLastUpdated = null;

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
