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
use App\Service\Payment\Helper\LpaIdHelper;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Uri;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;

/**
 * Initiates a GOV.UK Pay payment or resumes an existing one.
 *
 * @psalm-suppress UndefinedPropertyFetch
 */
class CheckoutPayHandler implements RequestHandlerInterface, LoggerAwareInterface
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
    ) {
        $this->lpaApplicationService = $lpaApplicationService;
        $this->communicationService = $communicationService;
        $this->urlHelper = $urlHelper;
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
        if (!is_null($lpa->payment->gatewayReference)) {
            $gatewayReference = $lpa->payment->gatewayReference;
            $payment = $this->paymentClient->getPayment($gatewayReference);

            if (is_null($payment)) {
                throw new RuntimeException(
                    'Invalid GovPay payment reference: ' . $gatewayReference
                );
            }

            if ($payment->isSuccess()) {
                // Payment already completed — record it and finish.
                $lpa->payment->method    = Payment::PAYMENT_TYPE_CARD;
                $lpa->payment->reference = $payment->reference;
                $lpa->payment->date      = new \DateTime();
                $govPayEmail = $payment->email ?? null;

                if (is_string($govPayEmail) && $govPayEmail !== '') {
                    $lpa->payment->email = new EmailAddress(['address' => strtolower($govPayEmail)]);
                } else {
                    $this->getLogger()->debug('GovPay returned no email for completed payment', [
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
                        'has_email'        => is_string($govPayEmail) && $govPayEmail !== '',
                    ]);
                }

                return $this->finishCheckout($lpa, $request);
            }

            if (!$payment->isFinished()) {
                return new RedirectResponse((string) $payment->getPaymentPageUrl());
            }
        }

        // Create a new payment
        $ref = LpaIdHelper::constructPaymentTransactionId((string) $lpa->id);

        $description = (
            $lpa->document->type == 'property-and-financial'
                ? 'Property and financial affairs'
                : 'Health and welfare'
        );
        $description .= ' LPA for ' . (string) $lpa->document->donor->name;

        // Build the callback URL using the request URI
        $requestUri = $request->getUri();
        $baseUrl = $requestUri->getScheme() . '://' . $requestUri->getAuthority();
        $callback = $baseUrl . $this->urlHelper->generate(
            'lpa/checkout/pay/response',
            ['lpa-id' => $lpa->id]
        );

        $payment = $this->paymentClient->createPayment(
            (int) ($lpa->payment->amount * 100.0), // amount in pence
            $ref,
            $description,
            new Uri($callback)
        );

        $lpa->payment->gatewayReference = $payment->payment_id;

        $this->lpaApplicationService->updateApplication($lpa->id, ['payment' => $lpa->payment->toArray()]);

        return new RedirectResponse((string) $payment->getPaymentPageUrl());
    }
}
