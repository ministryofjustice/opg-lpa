<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DonorIndexHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly MvcUrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        $addUrl = $this->urlHelper->generate('lpa/donor/add', ['lpa-id' => $lpa->id]);
        $editUrl = null;
        $nextUrl = null;

        if ($lpa->document->donor instanceof Donor) {
            $nextRoute = $flowChecker->nextRoute($currentRoute);

            $editUrl = $this->urlHelper->generate('lpa/donor/edit', ['lpa-id' => $lpa->id]);
            $nextUrl = $this->urlHelper->generate(
                $nextRoute,
                ['lpa-id' => $lpa->id],
                $flowChecker->getRouteOptions($nextRoute)
            );
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/donor/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'addUrl' => $addUrl,
                    'editUrl' => $editUrl,
                    'nextUrl' => $nextUrl,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
