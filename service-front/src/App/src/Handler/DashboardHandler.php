<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\PaginationTrait;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouteResult;
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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);

        /** @var User $identity */
        $identity = $request->getAttribute(RequestAttribute::IDENTITY);

        $queryParams = $request->getQueryParams();
        $search = $queryParams['search'] ?? null;
        $page = (int) ($routeResult?->getMatchedParams()['page'] ?? 1);

        $lpasPerPage = 50;

        $lpasSummary = $this->lpaApplicationService->getLpaSummaries($search, $page, $lpasPerPage);
        $lpas = $lpasSummary['applications'] ?? [];
        $lpasTotalCount = $lpasSummary['total'] ?? count($lpas);

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

        $routeParams = array_merge(
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
        );

        return new HtmlResponse(
            $this->renderer->render('application/authenticated/dashboard/index.twig', $routeParams)
        );
    }
}
