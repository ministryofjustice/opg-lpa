<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PrimaryAttorneyHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        $lpaId = $lpa->id;

        $templateParams = [
            'addUrl' => $this->urlHelper->generate('lpa/primary-attorney/add', ['lpa-id' => $lpaId]),
        ];

        if (count($lpa->document->primaryAttorneys) > 0) {
            $attorneysParams = [];

            foreach ($lpa->document->primaryAttorneys as $idx => $attorney) {
                $routeParams = [
                    'lpa-id' => $lpaId,
                    'idx' => $idx,
                ];

                $attorneysParams[] = [
                    'attorney' => [
                        'address' => $attorney->address,
                        'name' => $attorney->name,
                    ],
                    'editUrl' => $this->urlHelper->generate(
                        'lpa/primary-attorney/edit',
                        $routeParams
                    ),
                    'confirmDeleteUrl' => $this->urlHelper->generate(
                        'lpa/primary-attorney/confirm-delete',
                        $routeParams
                    ),
                ];
            }

            $templateParams['attorneys'] = $attorneysParams;

            $nextRoute = $flowChecker->nextRoute($currentRoute);
            $templateParams['nextUrl'] = $this->urlHelper->generate(
                $nextRoute,
                ['lpa-id' => $lpaId],
                $flowChecker->getRouteOptions($nextRoute)
            );
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/primary-attorney/index.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
