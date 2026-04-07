<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SummaryHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $queryParams = $request->getQueryParams();
        $returnRoute = isset($queryParams['return-route']) && is_string($queryParams['return-route'])
            ? $queryParams['return-route']
            : 'lpa/applicant';

        $isRepeatApplication = ($lpa->repeatCaseNumber !== null);

        $lowIncomeFee = Calculator::getLowIncomeFee($isRepeatApplication);
        $fullFee = Calculator::getFullFee($isRepeatApplication);

        $html = $this->renderer->render(
            'application/authenticated/lpa/summary/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'returnRoute' => $returnRoute,
                    'fullFee' => $fullFee,
                    'lowIncomeFee' => $lowIncomeFee,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
