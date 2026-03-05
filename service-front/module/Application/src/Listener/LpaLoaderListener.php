<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\FormFlowChecker;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as MVCResponse;
use RuntimeException;

class LpaLoaderListener extends AbstractListenerAggregate
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly LpaApplicationService $lpaApplicationService,
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
        $routeMatch = $event->getRouteMatch();

        if ($routeMatch === null) {
            return null;
        }

        $currentRoute = $routeMatch->getMatchedRouteName();

        // Skip flow checking for dashboard routes that have lpa-id but aren't LPA form steps
        if ($currentRoute === null || str_starts_with($currentRoute, 'user/dashboard/')) {
            return null;
        }

        $lpaId = $routeMatch->getParam('lpa-id');

        if ($lpaId === null) {
            return null;
        }

        $identity = $this->authenticationService->getIdentity();

        if (!$identity instanceof User) {
            return null;
        }

        $lpa = $this->lpaApplicationService->getApplication((int) $lpaId);

        if ($lpa === false) {
            $response = new Response();
            $response->setStatusCode(404);
            return $response;
        }

        if ($identity->id() !== $lpa->user) {
            throw new RuntimeException('Invalid LPA - current user can not access it');
        }

        $flowChecker = new FormFlowChecker($lpa);

        if ($currentRoute === 'lpa/download') {
            $param = $routeMatch->getParam('pdf-type');
        } else {
            $param = $routeMatch->getParam('idx');
        }

        $calculatedRoute = $flowChecker->getNearestAccessibleRoute($currentRoute, $param);

        if ($calculatedRoute === false) {
            return $event->getResponse();
        }

        if ($calculatedRoute !== $currentRoute) {
            $router = $event->getRouter();
            $url = $router->assemble(
                ['lpa-id' => $lpa->id],
                array_merge(
                    ['name' => $calculatedRoute],
                    $flowChecker->getRouteOptions($calculatedRoute)
                )
            );

            $response = new Response();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);
            return $response;
        }

        $event->setParam(EventParameter::LPA, $lpa);
        $event->setParam(EventParameter::FLOW_CHECKER, $flowChecker);
        $event->setParam(EventParameter::CURRENT_ROUTE, $currentRoute);

        return null;
    }
}
