<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Lpa\Traits\CheckoutTrait;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Communication;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckoutIndexHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use CheckoutTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        LpaApplicationService $lpaApplicationService,
        Communication $communicationService,
        MvcUrlHelper $urlHelper,
    ) {
        $this->lpaApplicationService = $lpaApplicationService;
        $this->communicationService = $communicationService;
        $this->urlHelper = $urlHelper;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $isPost = strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST;

        if ($isPost && !$this->isLpaComplete($lpa, $request)) {
            return $this->redirectToMoreInfoRequired($lpa, $request);
        }

        $isRepeatApplication = ($lpa->repeatCaseNumber != null);

        $lowIncomeFee = Calculator::getLowIncomeFee($isRepeatApplication);
        $fullFee = Calculator::getFullFee($isRepeatApplication);

        /** @var \Application\Form\Lpa\BlankMainFlowForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\BlankMainFlowForm', [
            'lpa' => $lpa,
        ]);

        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/checkout/pay', ['lpa-id' => $lpa->id])
        );
        $form->setAttribute('class', 'js-single-use');
        $form->get('submit')->setAttribute('value', 'Confirm and pay by card');
        $form->get('submit')->setAttribute('data-cy', 'confirm-and-pay-by-card');

        $html = $this->renderer->render(
            'application/authenticated/lpa/checkout/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'           => $form,
                    'lowIncomeFee'   => $lowIncomeFee,
                    'fullFee'        => $fullFee,
                    'lpaIsCompleted' => $this->isLpaComplete($lpa, $request),
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
