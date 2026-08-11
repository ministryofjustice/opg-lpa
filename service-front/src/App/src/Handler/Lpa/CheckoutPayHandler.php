<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Handler\Lpa\Traits\CheckoutTrait;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\CardPayments;
use App\Service\Payment\Helper\LpaIdHelper;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Uri;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Initiates a GOV.UK Pay payment or resumes an existing one.
 *
 * @psalm-suppress UndefinedPropertyFetch
 */
class CheckoutPayHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use CheckoutTrait;

    public function __construct(
        private readonly FormElementManager $formElementManager,
        LpaApplicationService $lpaApplicationService,
        Communication $communicationService,
        private readonly GovPayClient $paymentClient,
        UrlHelper $urlHelper,
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

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        if (!$this->isLpaComplete($lpa, $request)) {
            return $this->redirectToMoreInfoRequired($lpa, $request);
        }

        /** @var \App\Form\Lpa\BlankMainFlowForm $form */
        $form = $this->formElementManager->get('App\Form\Lpa\BlankMainFlowForm', [
            'lpa' => $lpa,
        ]);

        $isPost = strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST;

        if ($isPost) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if (!$form->isValid()) {
                return new RedirectResponse(
                    $this->urlHelper->generate(
                        'lpa/checkout',
                        ['lpa-id' => $lpa->id],
                        $flowChecker->getRouteOptions('lpa/checkout')
                    )
                );
            }
        }

        $this->verifyLpaPaymentAmount($lpa);

        // Check for any existing payments in play
        if (!is_null($lpa->getPayment()->getGatewayReference())) {
            $gatewayReference = $lpa->getPayment()->getGatewayReference();
            $payment          = $this->paymentClient->getPayment($gatewayReference);

            if (is_null($payment)) {
                throw new RuntimeException(
                    'Invalid GovPay payment reference: ' . $gatewayReference
                );
            }

            if ($payment->isSuccess()) {
                // Payment already completed — record it and finish.
                $this->cardPayments->recordSuccessfulPayment($lpa, $payment);

                return $this->finishCheckout($lpa, $request);
            }

            if (!$payment->isFinished()) {
                return new RedirectResponse((string) $payment->getPaymentPageUrl());
            }
        }

        // Create a new payment
        $ref = LpaIdHelper::constructPaymentTransactionId((string) $lpa->getId());

        $description = (
            $lpa->getDocument()->getType() == 'property-and-financial'
                ? 'Property and financial affairs'
                : 'Health and welfare'
        );
        $description .= ' LPA for ' . (string) $lpa->getDocument()->getDonor()->getName();

        // Build the callback URL using the request URI
        $requestUri = $request->getUri();
        $baseUrl = $requestUri->getScheme() . '://' . $requestUri->getAuthority();
        $callback = $baseUrl . $this->urlHelper->generate(
            'lpa/checkout/pay/response',
            ['lpa-id' => $lpa->getId()]
        );

        $payment = $this->paymentClient->createPayment(
            (int) ($lpa->getPayment()->getAmount() * 100.0), // amount in pence
            $ref,
            $description,
            new Uri($callback)
        );

        $lpa->getPayment()->setGatewayReference($payment->payment_id);

        $this->logger->info('payment created with GOV UK Pay', [
            'lpaId'            => $lpa->getId(),
            'gateway_reference' => $lpa->getPayment()->getGatewayReference(),
        ]);

        $this->lpaApplicationService->updateApplication($lpa->getId(), ['payment' => $lpa->getPayment()->toArray()]);

        $this->logger->info('LPA updated with payment information, redirecting to gov.uk pay', [
            'lpaId'            => $lpa->getId(),
            'payment' => $lpa->getPayment()->toJson(),
        ]);

        return new RedirectResponse((string) $payment->getPaymentPageUrl());
    }
}
