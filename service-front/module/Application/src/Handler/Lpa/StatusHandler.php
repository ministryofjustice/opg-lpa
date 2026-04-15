<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\View\StatusViewDataBuilder;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StatusHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly array $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $viewData = null;

        if ($lpa->getCompletedAt() instanceof DateTime) {
            $trackFromDate = null;
            if (isset($this->config['processing-status']['track-from-date'])) {
                $trackFromDate = new DateTime($this->config['processing-status']['track-from-date']);
            }

            $expectedWorkingDaysBeforeReceipt = null;
            if (isset($this->config['processing-status']['expected-working-days-before-receipt'])) {
                $expectedWorkingDaysBeforeReceipt =
                    intval($this->config['processing-status']['expected-working-days-before-receipt']);
            }

            $lpaStatusDetails = $this->lpaApplicationService->getStatuses($lpa->getId());

            $builder = new StatusViewDataBuilder();

            $viewData = $builder->build(
                $lpa,
                $lpaStatusDetails,
                $trackFromDate,
                $expectedWorkingDaysBeforeReceipt,
            );
        }

        if ($viewData === null) {
            return new RedirectResponse('/user/dashboard');
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/status/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                $viewData->toArray()
            )
        );

        return new HtmlResponse($html);
    }
}
