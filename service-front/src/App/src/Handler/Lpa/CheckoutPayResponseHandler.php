<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Handler\Lpa\Traits\CheckoutTrait;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\CardPayments;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Handles the callback from GOV.UK Pay after a user completes (or abandons) payment.
 *
 * @psalm-suppress UndefinedPropertyFetch
 */
class CheckoutPayResponseHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use CheckoutTrait;

    public function __construct(
        private readonly FormElementManager $formElementManager,
        LpaApplicationService $lpaApplicationService,
        Communication $communicationService,
        private readonly GovPayClient $paymentClient,
        UrlHelper $urlHelper,
        private readonly TemplateRendererInterface $renderer,
        private readonly LoggerInterface $logger,
        private readonly CardPayments $cardPayments,
    ) {
        $this->lpaApplicationService = $lpaApplicationService;
        $this->communicationService  = $communicationService;
        $this->urlHelper             = $urlHelper;
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

        $this->logger->info('PayResponse: GovPay lookup complete', [
            'lpaId'            => $lpa->id,
            'gatewayReference' => $gatewayReference,
            'responseIsNull'   => $paymentResponse === null,
            'status'           => $paymentResponse?->state->status ?? null,
            'finished'         => $paymentResponse?->state->finished ?? null,
            'stateCode'        => $paymentResponse?->state->code ?? null,
        ]);

        if ($paymentResponse === null) {
            $this->logger->error('GovPay payment lookup returned null — payment may not exist yet or gateway reference is invalid', [
                'lpaId'            => $lpa->id,
                'gatewayReference' => $gatewayReference,
            ]);

            return new RedirectResponse(
                $this->urlHelper->generate('lpa/checkout/pay', ['lpa-id' => $lpa->id])
            );
        }

        if (!$paymentResponse->isSuccess()) {
            $this->logger->info('PayResponse: payment not successful, rendering failure/cancel page', [
                'lpaId'     => $lpa->id,
                'stateCode' => $paymentResponse->state->code ?? null,
                'status'    => $paymentResponse->state->status ?? null,
            ]);

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

            $template = ($paymentResponse->state->code ?? null) === 'P0030'
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

        $this->logger->info('PayResponse: payment successful, recording on LPA', [
            'lpaId'            => $lpa->id,
            'gatewayReference' => $gatewayReference,
            'has_email'        => isset($paymentResponse->email) && $paymentResponse->email !== '',
        ]);

        // Payment succeeded at GovPay — record it on the LPA.
        $recorded = $this->cardPayments->recordSuccessfulPayment($lpa, $paymentResponse);

        $this->logger->info('PayResponse: updateApplication result', [
            'lpaId'   => $lpa->id,
            'success' => $recorded,
        ]);

        return $this->finishCheckout($lpa, $request);
    }
}
