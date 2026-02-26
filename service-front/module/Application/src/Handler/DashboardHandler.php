<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\PaginationTrait;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Router\RouteMatch;
use Mezzio\Template\TemplateRendererInterface;
use MakeShared\Logging\LoggerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;

class DashboardHandler implements RequestHandlerInterface, LoggerAwareInterface
{
    use LoggerTrait;
    use PaginationTrait;
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly AuthenticationService $authenticationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);

        $identity = $this->authenticationService->getIdentity();

        $queryParams = $request->getQueryParams();
        $search = $queryParams['search'] ?? null;
        $page = (int) $routeMatch?->getParam('page', 1);

        $lpasPerPage = 50;

        $lpasSummary = $this->lpaApplicationService->getLpaSummaries($search, $page, $lpasPerPage);
        $lpas = $lpasSummary['applications'];
        $lpasTotalCount = $lpasSummary['total'];

        // If there are no LPAs and this is NOT a search, redirect to create
        if (is_null($search) && count($lpas) == 0) {
            return new RedirectResponse('/user/dashboard/create');
        }

        $pagesInRange = 5;
        $paginationControlData = $this->getPaginationControlData(
            $page,
            $lpasPerPage,
            $lpasTotalCount,
            $pagesInRange
        );

        return new HtmlResponse(
            $this->renderer->render('application/authenticated/dashboard/index.twig', array_merge(
                $this->getTemplateVariables($request),
                [
                        'lpas'                  => $lpas,
                        'lpaTotalCount'         => $lpasTotalCount,
                        'paginationControlData' => $paginationControlData,
                        'freeText'              => $search,
                        'isSearch'              => (is_string($search) && !empty($search)),
                        'user'                  => [
                            'lastLogin' => $identity->lastLogin(),
                        ],
                        'trackingEnabled' => $lpasSummary['trackingEnabled'],
                    ]
            ))
        );
    }
}
