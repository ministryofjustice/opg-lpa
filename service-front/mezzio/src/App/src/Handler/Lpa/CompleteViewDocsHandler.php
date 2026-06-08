<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\CompleteViewParamsHelper;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CompleteViewDocsHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly CompleteViewParamsHelper $completeViewParamsHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        if ($lpa->locked !== true) {
            $this->lpaApplicationService->lockLpa($lpa);
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/complete/view-docs.twig',
            array_merge(
                $this->getTemplateVariables($request),
                $this->completeViewParamsHelper->build($lpa)
            )
        );

        return new HtmlResponse($html);
    }
}
