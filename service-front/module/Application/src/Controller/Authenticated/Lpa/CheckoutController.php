<?php

namespace Application\Controller\Authenticated\Lpa;

use Alphagov\Pay\Client as GovPayClient;
use Application\Controller\AbstractLpaController;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Payment\Helper\LpaIdHelper;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Laminas\View\Helper\ServerUrl;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
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
class CheckoutController extends AbstractLpaController
{
    use LoggerTrait;

    /** @var Communication */
    private $communicationService;

    /** @var GovPayClient */
    private $paymentClient;

    public function indexAction()
    {
        $request = $this->convertRequest();

        if ($request->isPost() && !$this->isLPAComplete()) {
            return $this->redirectToMoreInfoRequired();
        }

        $lpa = $this->getLpa();
        $isRepeatApplication = ($lpa->repeatCaseNumber != null);

        $lowIncomeFee = Calculator::getLowIncomeFee($isRepeatApplication);
        $fullFee = Calculator::getFullFee($isRepeatApplication);

        // set hidden form for confirming and paying by card.
        $form = $this->getFormElementManager()->get('Application\Form\Lpa\BlankMainFlowForm', [
            'lpa' => $lpa
        ]);

        $form->setAttribute(
            'action',
            $this->url()->fromRoute('lpa/checkout/pay', ['lpa-id' => $lpa->id])
        );
        $form->setAttribute('class', 'js-single-use');
        $form->get('submit')->setAttribute('value', 'Confirm and pay by card');
        $form->get('submit')->setAttribute('data-cy', 'confirm-and-pay-by-card');

        return new ViewModel([
            'form'           => $form,
            'lowIncomeFee'   => $lowIncomeFee,
            'fullFee'        => $fullFee,
            'lpaIsCompleted' => $this->isLPAComplete(),
        ]);
    }

    private function redirectToMoreInfoRequired()
    {
        $route = 'lpa/more-info-required';

        $this->redirectToRoute(
            $route,
            ['lpa-id' => $this->getLpa()->id],
            $this->getFlowChecker()?->getRouteOptions($route)
        );

        return $this->getResponse();
    }

    public function chequeAction()
    {
        if (!$this->isLPAComplete()) {
            return $this->redirectToMoreInfoRequired();
        }

        $lpa = $this->getLpa();

        $lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;

        //  Verify that the payment amount associated with the LPA is corrected based on the fees right now
        $this->verifyLpaPaymentAmount($lpa);

        if (!$this->getLpaApplicationService()->setPayment($lpa, $lpa->payment)) {
            throw new RuntimeException(
                'API client failed to set payment details for id: ' . $lpa->id . ' in CheckoutController'
            );
        }

        return $this->finishCheckout();
    }

    public function confirmAction()
    {
        if (!$this->isLPAComplete()) {
            return $this->redirectToMoreInfoRequired();
        }

        $lpa = $this->getLpa();

        // Sanity check; making sure this method isn't called if there's something to pay.
        if (intval($lpa->payment->amount) !== 0) {
            throw new RuntimeException('Invalid option');
        }

        return $this->finishCheckout();
    }

    private function finishCheckout()
    {
        $lpa = $this->getLpa();

        // Lock the LPA form future changes
        $this->getLpaApplicationService()->lockLpa($lpa);

        // Send confirmation email
        $this->communicationService->sendRegistrationCompleteEmail($lpa);

        //  Don't use the next route function here - just go directly to the completed view
        return $this->redirectToRoute(
            'lpa/complete',
            ['lpa-id' => $this->getLpa()->id]
        );
    }

    // GDS Pay
    private function isLPAComplete()
    {
        return ($this->getLpa()->isStateCreated() && $this->getFlowChecker()->backToForm() == "lpa/checkout");
    }

    public function payAction()
    {
        if (!$this->isLPAComplete()) {
            return $this->redirectToMoreInfoRequired();
        }

        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\BlankMainFlowForm', [
            'lpa' => $lpa
        ]);

        // Confirm that pay by card form post was valid and redirect pack if not
        $request = $this->convertRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if (!$form->isValid()) {
                return $this->redirectToRoute(
                    'lpa/checkout',
                    ['lpa-id' => $this->getLpa()->id],
                    $this->getFlowChecker()->getRouteOptions('lpa/checkout')
                );
            }
        }

        //  Verify that the payment amount associated with the LPA is corrected based on the fees right now
        $this->verifyLpaPaymentAmount($lpa);

        $paymentClient = $this->paymentClient;

        // Check for any existing payments in play
        if (!is_null($lpa->payment->gatewayReference)) {
            // Look the payment up on Pay
            $payment = $paymentClient->getPayment($lpa->payment->gatewayReference);

            // If the payment is null (possibly due to bad ref) then redirect to the failure view
            if (is_null($payment)) {
                throw new RuntimeException('Invalid GovPay payment reference: ' . $lpa->payment->gatewayReference);
            }

            if ($payment->isSuccess()) {
                // Payment has already been made.
                return $this->payResponseAction();
            }

            // If this payment id is still in play, direct the user back...
            if (!$payment->isFinished()) {
                return new RedirectResponse((string) $payment->getPaymentPageUrl());
            }

            // else carry on to start a new payment
        }

        // Create a new payment
        $ref = LpaIdHelper::constructPaymentTransactionId($lpa->id);

        $description =  (
            $lpa->document->type == 'property-and-financial' ? 'Property and financial affairs' : 'Health and welfare'
        );
        $description .= " LPA for " . (string)$lpa->document->donor->name;

        // General URL to return the user to.
        $callback = (new ServerUrl())->__invoke(false) . $this->url()->fromRoute(
            'lpa/checkout/pay/response',
            ['lpa-id' => $lpa->id]
        );

        $payment = $paymentClient->createPayment(
            (int)($lpa->payment->amount * 100.0), // amount in pence,
            $ref,
            $description,
            new \GuzzleHttp\Psr7\Uri($callback)
        );

        // Store the gateway reference
        $lpa->payment->gatewayReference = $payment->payment_id;

        $this->getLpaApplicationService()->updateApplication($lpa->id, ['payment' => $lpa->payment->toArray()]);

        return new RedirectResponse((string) $payment->getPaymentPageUrl());
    }

    /**
     * Simple function to confirm that the payment amount currently
     * associated with the LPA is correct based on the fees right now.
     * If the amount has changed them set the new value and null any
     * gateway reference so a new transaction is issued.
     *
     * @param Lpa $lpa
     */
    private function verifyLpaPaymentAmount(Lpa $lpa)
    {
        $lpaPayment = $lpa->payment;

        if ($lpaPayment instanceof Payment) {
            $existingPaymentAmount = $lpaPayment->amount;

            // Run the LPA through the calculator to determine if the amount is still the same
            Calculator::calculate($lpa);

            // If the value has changed then save the new amount and null any existing transaction
            if ($existingPaymentAmount != $lpaPayment->amount) {
                // Blank any existing transaction
                $lpaPayment->gatewayReference = null;

                // Save the LPA to update the details
                if (!$this->getLpaApplicationService()->setPayment($lpa, $lpaPayment)) {
                    throw new RuntimeException(
                        'API client failed to set payment details for id: ' . $lpa->id . ' in CheckoutController'
                    );
                }
            }
        }
    }

    public function payResponseAction()
    {
        $lpa = $this->getLpa();

        // Lookup the payment ID in play...
        if (is_null($lpa->payment->gatewayReference)) {
            throw new RuntimeException('Payment id needed');
        }

        $paymentClient = $this->paymentClient;

        $paymentResponse = $paymentClient->getPayment($lpa->payment->gatewayReference);

        // If the payment was not successful...
        if (!$paymentResponse->isSuccess()) {
            // set hidden form for confirming and paying by card.
            $form = $this->getFormElementManager()->get('Application\Form\Lpa\BlankMainFlowForm', [
                'lpa' => $lpa
            ]);

            $form->setAttribute(
                'action',
                $this->url()->fromRoute('lpa/checkout/pay', ['lpa-id' => $lpa->id])
            );
            $form->setAttribute('class', 'js-single-use');
            $form->get('submit')->setAttribute('value', 'Retry online payment');

            $viewModel = new ViewModel([
                'form' => $form
            ]);

            // If the user actively canceled it...
            if ($paymentResponse->state->code == 'P0030') {
                $viewModel->setTemplate('application/authenticated/lpa/checkout/govpay-cancel.twig');
            } else {
                // Else it failed for some other reason.
                $viewModel->setTemplate('application/authenticated/lpa/checkout/govpay-failure.twig');
            }

            return $viewModel;
        }

        // The payment is all good, so add the details
        $lpa->payment->method = Payment::PAYMENT_TYPE_CARD;
        $lpa->payment->reference = $paymentResponse->reference;
        $lpa->payment->date = new \DateTime();
        $lpa->payment->email = new EmailAddress(['address' => strtolower($paymentResponse->email)]);

        $this->getLpaApplicationService()->updateApplication($lpa->id, ['payment' => $lpa->payment->toArray()]);

        return $this->finishCheckout();
    }

    public function setCommunicationService(Communication $communicationService)
    {
        $this->communicationService = $communicationService;
    }

    public function setPaymentClient(GovPayClient $paymentClient)
    {
        $this->paymentClient = $paymentClient;
    }
}
