<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\FormFlowChecker;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as MVCResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class LpaLoaderListener extends AbstractListenerAggregate implements MiddlewareInterface
{
    public const string ATTR_LPA = Lpa::class;
    public const string ATTR_FLOW_CHECKER = FormFlowChecker::class;
    public const string ATTR_CURRENT_ROUTE = 'currentRouteName';

    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly LpaApplicationService $lpaApplicationService,
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
        $routeMatch = $event->getRouteMatch();

        if ($routeMatch === null) {
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
        $currentRoute = $routeMatch->getMatchedRouteName();

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

        $event->setParam(self::ATTR_LPA, $lpa);
        $event->setParam(self::ATTR_FLOW_CHECKER, $flowChecker);
        $event->setParam(self::ATTR_CURRENT_ROUTE, $currentRoute);

        return null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);

        if ($routeResult === null || $routeResult->isFailure()) {
            return $handler->handle($request);
        }

        $params = $routeResult->getMatchedParams();
        $lpaId = $params['lpa-id'] ?? null;

        if ($lpaId === null) {
            return $handler->handle($request);
        }

        $identity = $this->authenticationService->getIdentity();

        if (!$identity instanceof User) {
            return $handler->handle($request);
        }

        $lpa = $this->lpaApplicationService->getApplication((int) $lpaId);

        if ($lpa === false) {
            return new \Laminas\Diactoros\Response\HtmlResponse(
                'The requested LPA could not be found',
                404
            );
        }

        if ($identity->id() !== $lpa->user) {
            throw new RuntimeException('Invalid LPA - current user can not access it');
        }

        $flowChecker = new FormFlowChecker($lpa);
        $currentRoute = $routeResult->getMatchedRouteName() ?? '';

        if ($currentRoute === 'lpa/download') {
            $param = $params['pdf-type'] ?? null;
        } else {
            $param = $params['idx'] ?? null;
        }

        $calculatedRoute = $flowChecker->getNearestAccessibleRoute($currentRoute, $param);

        if ($calculatedRoute === false) {
            return new EmptyResponse();
        }

        if ($calculatedRoute !== $currentRoute) {
            $url = $this->urlHelper->generate(
                $calculatedRoute,
                ['lpa-id' => $lpa->id],
                $flowChecker->getRouteOptions($calculatedRoute)
            );

            return new RedirectResponse($url);
        }

        $request = $request
            ->withAttribute(self::ATTR_LPA, $lpa)
            ->withAttribute(self::ATTR_FLOW_CHECKER, $flowChecker)
            ->withAttribute(self::ATTR_CURRENT_ROUTE, $currentRoute);

        return $handler->handle($request);
    }
}
