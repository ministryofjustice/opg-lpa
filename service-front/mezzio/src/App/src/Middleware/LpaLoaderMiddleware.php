<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Model\Service\Authentication\Identity\User;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LpaLoaderMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);

        if (!$routeResult instanceof RouteResult || $routeResult->isFailure()) {
            return $handler->handle($request);
        }

        $params = $routeResult->getMatchedParams();
        $lpaId  = $params['lpa-id'] ?? null;

        if ($lpaId === null) {
            return $handler->handle($request);
        }

        $identity = $request->getAttribute(RequestAttribute::IDENTITY);

        if (!$identity instanceof User) {
            return $handler->handle($request);
        }

        $lpa = $this->lpaApplicationService->getApplication((int) $lpaId);

        if ($lpa === false) {
            return new HtmlResponse('The requested LPA could not be found', 404);
        }

        if ($identity->id() !== $lpa->user) {
            return new HtmlResponse('The requested LPA could not be found', 404);
        }

        $flowChecker  = new FormFlowChecker($lpa);
        $currentRoute = $routeResult->getMatchedRouteName() ?: '';

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
                $flowChecker->getRouteOptions($calculatedRoute),
            );

            return new RedirectResponse($url);
        }

        $request = $request
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker);

        return $handler->handle($request);
    }
}
