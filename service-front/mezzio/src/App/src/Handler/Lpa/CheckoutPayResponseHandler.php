<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Handler\Lpa\Traits\CheckoutTrait;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;

/**
 * Handles the callback from GOV.UK Pay after a user completes (or abandons) payment.
 *
 * @psalm-suppress UndefinedPropertyFetch
 */
class CheckoutPayResponseHandler implements RequestHandlerInterface, LoggerAwareInterface
{
    use CommonTemplateVariablesTrait;
    use CheckoutTrait;
    use LoggerTrait;

    public function __construct(
        private readonly FormElementManager $formElementManager,
        LpaApplicationService $lpaApplicationService,
        Communication $communicationService,
        private readonly GovPayClient $paymentClient,
        UrlHelper $urlHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
        $this->lpaApplicationService = $lpaApplicationService;
        $this->communicationService = $communicationService;
        $this->urlHelper = $urlHelper;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        if (is_null($lpa->payment->gatewayReference)) {
            throw new RuntimeException('Payment id needed');
        }

        $gatewayReference = $lpa->payment->gatewayReference;

        $paymentResponse = $this->paymentClient->getPayment($gatewayReference);

        if (!$paymentResponse->isSuccess()) {
            /** @var \App\Form\Lpa\BlankMainFlowForm $form */
            $form = $this->formElementManager->get('App\Form\Lpa\BlankMainFlowForm', [
                'lpa' => $lpa,
            ]);

            $form->setAttribute(
                'action',
                $this->urlHelper->generate('lpa/checkout/pay', ['lpa-id' => $lpa->id])
            );
            $form->setAttribute('class', 'js-single-use');
            $form->get('submit')->setAttribute('value', 'Retry online payment');

            $template = $paymentResponse->state->code === 'P0030'
                ? 'application/authenticated/lpa/checkout/govpay-cancel.twig'
                : 'application/authenticated/lpa/checkout/govpay-failure.twig';

            $html = $this->renderer->render(
                $template,
                array_merge(
                    $this->getTemplateVariables($request),
                    ['form' => $form]
                )
            );

            return new HtmlResponse($html);
        }

        // Payment succeeded at GovPay — record it on the LPA.
        $lpa->payment->method    = Payment::PAYMENT_TYPE_CARD;
        $lpa->payment->reference = $paymentResponse->reference;
        $lpa->payment->date      = new \DateTime();

        $govPayEmail = $paymentResponse->email ?? null;

        if (is_string($govPayEmail) && $govPayEmail !== '') {
            $lpa->payment->email = new EmailAddress(['address' => strtolower($govPayEmail)]);
        } else {
            $this->getLogger()->warning('GovPay returned no email for completed payment', [
                'lpaId'            => $lpa->id,
                'gatewayReference' => $gatewayReference,
                'email_raw'        => $govPayEmail,
            ]);
            $lpa->payment->email = null;
        }

        $result = $this->lpaApplicationService->updateApplication($lpa->id, ['payment' => $lpa->payment->toArray()]);

        if ($result === false) {
            $this->getLogger()->critical('PAYMENT RECORDING FAILED — payment taken but LPA not updated', [
                'lpaId'            => $lpa->id,
                'gatewayReference' => $gatewayReference,
                'govpay_status'    => $paymentResponse->state->status ?? 'unknown',
                'has_email'        => is_string($govPayEmail) && $govPayEmail !== '',
            ]);
        }

        return $this->finishCheckout($lpa, $request);
    }
}
