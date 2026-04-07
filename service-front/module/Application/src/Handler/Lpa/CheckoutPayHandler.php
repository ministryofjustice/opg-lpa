<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Alphagov\Pay\Client as GovPayClient;
use Application\Handler\Lpa\Traits\CheckoutTrait;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Payment\Helper\LpaIdHelper;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Uri;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Note: Alphagov\Pay\Response\Payment uses magic methods to
 * convert the response from gov pay to an array object whose
 * contents are accessible as properties. Psalm doesn't
 * understand this (it can't tell what the gov pay response
 * looks like), and raises UndefinedPropertyFetch errors.
 * That's why they are suppressed throughout this class.
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

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        if (!$this->isLpaComplete($lpa, $request)) {
            return $this->redirectToMoreInfoRequired($lpa, $request);
        }

        /** @var \Application\Form\Lpa\BlankMainFlowForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\BlankMainFlowForm', [
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
            $payment = $this->paymentClient->getPayment($lpa->payment->gatewayReference);

            if (is_null($payment)) {
                throw new RuntimeException(
                    'Invalid GovPay payment reference: ' . $lpa->payment->gatewayReference
                );
            }

            if ($payment->isSuccess()) {
                // Payment already completed - record it and finish
                $lpa->payment->method    = Payment::PAYMENT_TYPE_CARD;
                $lpa->payment->reference = $payment->reference;
                $lpa->payment->date      = new \DateTime();
                $lpa->payment->email     = new EmailAddress(['address' => strtolower($payment->email)]);

                $this->lpaApplicationService->updateApplication($lpa->id, ['payment' => $lpa->payment->toArray()]);

                return $this->finishCheckout($lpa);
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
