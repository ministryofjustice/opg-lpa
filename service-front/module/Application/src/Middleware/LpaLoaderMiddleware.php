<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Helper\MvcUrlHelper;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Loads the LPA for the current request, validates that the authenticated user owns
 * it, and checks the form flow to ensure the user can access the requested route. If
 * the flow checker determines a different route should be shown first, the user is
 * redirected there. Returns a 404 response if the LPA cannot be found, and throws a
 * RuntimeException if the LPA belongs to a different user. On success, the LPA and a
 * FormFlowChecker instance are set as request attributes keyed by RequestAttribute::LPA
 * and RequestAttribute::FLOW_CHECKER for downstream handlers to consume.
 *
 * Requires the authenticated identity to already be set as RequestAttribute::IDENTITY
 * on the request (i.e. AuthenticationListener must run before this middleware).
 *
 * This is the PSR-7 equivalent of LpaLoaderListener.
 */
class LpaLoaderMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
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
            return new HtmlResponse(
                'The requested LPA could not be found',
                404
            );
        }

        if ($identity->id() !== $lpa->user) {
            throw new RuntimeException('Invalid LPA - current user can not access it');
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
                $flowChecker->getRouteOptions($calculatedRoute)
            );

            return new RedirectResponse($url);
        }

        $request = $request
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker);

        return $handler->handle($request);
    }
}
