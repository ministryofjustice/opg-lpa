<?php

namespace Application\Controller\Authenticated\Lpa;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Zend\View\Helper\ServerUrl;

use Zend\Http\Response as HttpResponse;

use Application\Model\Service\Payment\Helper\LpaIdHelper;

class CheckoutController extends AbstractLpaController {

    public function indexAction(){

        $segmentsSentToGovPay = 100;

        // Take the user's id and puts them into a segment between 1 and 100.
        $segment = (abs(crc32( $this->getUser()->id() )) % 100) + 1;

        $userGdsPay = ($segment <= $segmentsSentToGovPay);

        //---

        $paymentViewVars = array();

        if( !$userGdsPay ){

            $worldPayForm = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PaymentForm');

            $response = $this->processWorldPayForm( $worldPayForm );

            if( $response instanceof HttpResponse ){
                return $response;
            }

            $paymentViewVars['worldpayForm'] = $worldPayForm;

        }

        //---

        $lpa = $this->getLpa();

        return new ViewModel([
            // If it's not a while number, use money_format
            'paymentAmount' => ( floor( $lpa->payment->amount ) == $lpa->payment->amount ) ? $lpa->payment->amount : money_format('%i', $lpa->payment->amount),
        ] + $paymentViewVars);

    }

    public function chequeAction(){

        $lpa = $this->getLpa();

        $lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;

        if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
            throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in CheckoutController');
        }

        //---

        return $this->finishCheckout();

    }

    public function confirmAction(){

        $lpa = $this->getLpa();

        // Sanity check; making sure this method isn't called if there's something to pay.
        if( $lpa->payment->amount != 0 ){
            throw new \RuntimeException('Invalid option');
        }

        //---

        return $this->finishCheckout();

    }

    private function finishCheckout(){

        $lpa = $this->getLpa();

        //---

        // Lock the LPA form future changes.
        $this->getLpaApplicationService()->lockLpa( $this->getLpa()->id );

        //---

        // Send confirmation email.
        $this->getServiceLocator()->get('Communication')
            ->sendRegistrationCompleteEmail($lpa, $this->url()
                ->fromRoute('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true]));


        return $this->getNextSectionRedirect();

    }

    //------------------------------------------------------------------------------
    // GDS Pay


    public function payAction(){

        $lpa = $this->getLpa();

        $paymentClient = $this->getServiceLocator()->get('GovPayClient');

        //----------------------------
        // Check for any existing payments in play


        if( !is_null($lpa->payment->gatewayReference) ){

            // Look the payment up on Pay
            $payment = $paymentClient->getPayment( $lpa->payment->gatewayReference );

            if( $payment->isSuccess() ) {
                // Payment has already been made.
                return $this->payResponseAction();
            }

            // If this payment id is still in play, direct the user back...
            if( !$payment->isFinished() ){

                $this->redirect()->toUrl( $payment->getPaymentPageUrl() );
                return $this->getResponse();

            }

            // else carry on to start a new payment.

        }

        //----------------------------
        // Create a new payment

        $ref = LpaIdHelper::constructPaymentTransactionId( $lpa->id );

        $description =  ( $lpa->document->type == 'property-and-financial' ) ? 'Property and financial affairs' : 'Health and welfare';
        $description .= " LPA for ".(string)$lpa->document->donor->name;

        // General URL to return the user to.
        $callback = (new ServerUrl())->__invoke(false) . $this->url()->fromRoute(
                'lpa/checkout/pay/response',
                ['lpa-id' => $lpa->id]
            );

        //---

        $payment = $paymentClient->createPayment(
            (int)($lpa->payment->amount * 100), // amount in pence,
            $ref,
            $description,
            new \GuzzleHttp\Psr7\Uri($callback)
        );

        //---

        // Store the gateway reference

        $lpa->payment->gatewayReference = $payment->payment_id;

        $this->getLpaApplicationService()->updatePayment( $lpa );

        //---

        $this->redirect()->toUrl( $payment->getPaymentPageUrl() );
        return $this->getResponse();


    }


    public function payResponseAction(){

        $lpa = $this->getLpa();

        // Lookup the payment ID in play...

        if( is_null($lpa->payment->gatewayReference) ){
            die('Payment id needed');
        }

        //---

        $paymentClient = $this->getServiceLocator()->get('GovPayClient');

        $paymentResponse = $paymentClient->getPayment( $lpa->payment->gatewayReference );

        //---

        // If the payment was not successful...
        if( !$paymentResponse->isSuccess() ){

            $viewModel = new ViewModel();

            // If the user actively canceled it...
            if( $paymentResponse->state->code == 'P0030' ){

                $viewModel->setTemplate('application/checkout/govpay-cancel.twig');

            } else {

                // Else it failed for some other reason.
                $viewModel->setTemplate('application/checkout/govpay-failure.twig');

            }

            return $viewModel;

        }

        //---

        // Else the payment is all good.

        // Add the details
        $lpa->payment->method = Payment::PAYMENT_TYPE_CARD;
        $lpa->payment->reference = $paymentResponse->reference;
        $lpa->payment->date = new \DateTime('today');
        $lpa->payment->email = new EmailAddress( ['address'=>strtolower($paymentResponse->email)] );

        $this->getLpaApplicationService()->updatePayment( $lpa );

        //---

        return $this->finishCheckout();

    }

    //------------------------------------------------------------------------------
    // WorldPay

    public function worldpaySuccessAction(){

        $params = [
            'paymentStatus' => null,
            'orderKey' => null,
            'paymentAmount' => null,
            'paymentCurrency' => null,
            'mac' => null
        ];

        foreach ($params as $key => &$value) {
            if ($this->request->getQuery($key) == null) {
                throw new \Exception(
                    'Invalid success response from Worldpay. ' .
                    'Expected ' . $key . ' parameter was not found. ' .
                    $_SERVER["REQUEST_URI"]
                );
            }
            $value = $this->request->getQuery($key);
        }

        if ($params['paymentStatus'] != 'AUTHORISED') {
            throw new \Exception(
                'Invalid success response from Worldpay. ' .
                'paymentStatus was ' . $params['paymentStatus'] . ' (expected AUTHORISED)'
            );
        }

        //--------

        $paymentService = $this->getServiceLocator()->get('Payment');

        $paymentService->verifyMacString($params, $this->getLpa()->id);
        $paymentService->verifyOrderKey($params, $this->getLpa()->id);

        // The above functions throw fatal exceptions if there are any issues.

        $paymentService->updateLpa($params, $this->getLpa());

        //---

        return $this->finishCheckout();

    }

    public function worldpayCancelAction(){

        $worldPayForm = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PaymentForm');

        $response = $this->processWorldPayForm( $worldPayForm );

        if( $response instanceof HttpResponse ){
            return $response;
        }

        //---

        // Shows cancel page
        return new ViewModel([
            'worldpayForm' => $worldPayForm,
        ]);

    }

    public function worldpayFailureAction(){

        $worldPayForm = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PaymentForm');

        $response = $this->processWorldPayForm( $worldPayForm );

        if( $response instanceof HttpResponse ){
            return $response;
        }

        //---

        // Shows failure page
        return new ViewModel([
            'worldpayForm' => $worldPayForm,
        ]);

    }

    public function worldpayPendingAction(){

    }

    //---

    private function getWorldpayRedirect( $emailAddress ){

        $paymentService = $this->getServiceLocator()->get('Payment');

        $options = $paymentService->getOptions( $this->getLpa(), $emailAddress );

        $response = $paymentService
            ->getGateway()
            ->purchase($options)
            ->send();

        $redirectUrl = $response->getData()->reference;

        foreach( [ 'success', 'failure', 'cancel' ] as $type ){
            $redirectUrl .= "&{$type}URL=" . $this->getWorldpayRedirectCallbackEndpoint($type);
        }

        $this->redirect()->toUrl($redirectUrl);

        return $this->getResponse();

    }

    private function getWorldpayRedirectCallbackEndpoint($type) {

        $baseUri = (new ServerUrl())->__invoke(false);

        return $baseUri . $this->url()->fromRoute(
            'lpa/checkout/worldpay/return/' . $type,
            ['lpa-id' => $this->getLpa()->id]
        );

    }

    //-------------------------------------

    private function processWorldPayForm( $worldPayForm ){

        // If POST, it's a worldpay payment...
        if($this->request->isPost()) {

            $worldPayForm->setData( $this->request->getPost() );

            if($worldPayForm->isValid()) {

                $lpa = $this->getLpa();

                $lpa->payment->method = Payment::PAYMENT_TYPE_CARD;

                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in CheckoutController');
                }

                //---

                return $this->getWorldpayRedirect( $worldPayForm->getData()['email'] );

            }

        }

    } // processWorldPayForm

}
