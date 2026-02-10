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
 * We check if the terms have changed since the user last logged in.
 * We also use a session to record whether the user has seen the 'Terms have changed' page since logging in.
 *
 * If the terms have changed and they haven't seen the 'Terms have changed' page
 * in this session, we redirect them to it.
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
