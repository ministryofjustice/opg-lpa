<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\Payment\CardPayments;
use App\Service\Payment\Helper\CheckoutHelper;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class CheckoutIndexHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UrlHelper $urlHelper,
        private readonly CardPayments $cardPayments,
        private readonly CheckoutHelper $checkoutHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        try {
            // Using getVerison here as it isn't really a user initiated action,
            // and a getting a conflict would be meaningless.
            $ifMatchVersion = $lpa->getVersion();

            [$ifMatchVersion, $ok] = $this->cardPayments->recoverCompletedPayment($lpa, $ifMatchVersion);
            if ($ok) {
                return $this->checkoutHelper->finishCheckout($lpa, $request, $ifMatchVersion);
            }
        } catch (ConflictException $e) {
            $this->logger->info('Conflict raised when trying to check uncompleted payment', ['exception' => $e]);
        }

        $isPost = strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST;

        if ($isPost && !$this->checkoutHelper->isLpaComplete($lpa, $request)) {
            return $this->checkoutHelper->redirectToMoreInfoRequired($lpa, $request);
        }

        $isRepeatApplication = ($lpa->getRepeatCaseNumber() != null);

        $lowIncomeFee = Calculator::getLowIncomeFee($isRepeatApplication);
        $fullFee = Calculator::getFullFee($isRepeatApplication);

        /** @var \App\Form\Lpa\BlankMainFlowForm $form */
        $form = $this->formElementManager->get('App\Form\Lpa\BlankMainFlowForm', [
            'lpa' => $lpa,
        ]);

        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/checkout/pay', ['lpa-id' => $lpa->getId()])
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
                    'lpaIsCompleted' => $this->checkoutHelper->isLpaComplete($lpa, $request),
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
