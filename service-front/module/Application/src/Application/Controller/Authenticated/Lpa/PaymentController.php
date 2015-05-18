<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;

use Omnipay\Omnipay;
use Zend\View\Helper\ServerUrl;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class PaymentController extends AbstractLpaController
{
    protected $contentHeader = 'registration-partial.phtml';
    
    /**
     * Gathers the LPA information and forwards the payment request to Worldpay
     * Uses the Omnipay purchase interface to obtain a URL to which to redirect
     * the user for payment.
     */
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PaymentForm');
        
        $lpa = $this->getLpa();
        
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpa->payment->method = Payment::PAYMENT_TYPE_CARD;
                
                // persist data
                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpa->id);
                }
                
                // set paymentEmail in session container.
                $container = new Container('paymentEmail');
                $container->email = $form->getData()['email'];
                
                // init online payment
                $paymentService = $this->getServiceLocator()->get('Payment');
                
                $options = $paymentService->getOptions($lpa);
                
                $response = 
                    $paymentService
                         ->getGateway()
                         ->purchase($options)
                         ->send();
                
                $paymentGatewayBaseUrl = $response->getData()->reference;
                
                $redirectUrl = $this->getRedirectUrl($paymentGatewayBaseUrl);
                
                $this->redirect()->toUrl($redirectUrl);
                
                return $this->getResponse();
                
            } // if($form->isValid())
        }
        elseif($this->params()->fromQuery('pay-by-cheque')) {
            
            $lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;

            // payment date is only for online payment.
            //$lpa->payment->date = new \DateTime();
            
            if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in FeeReductionController');
            }
            
            // send email
            $communicationService = $this->getServiceLocator()->get('Communication');
            $communicationService->sendRegistrationCompleteEmail($lpa, $this->url()->fromRoute('lpa/created', ['lpa-id' => $lpa->id], ['force_canonical' => true]));
            
            // to complete page
            return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);
        }
        else {
            $data = [];
            if($this->getLpa()->payment instanceof Payment) {
                $data['method'] =  $this->getLpa()->payment->method;
            }
            
            $container = new Container('paymentEmail');
            if(isset($container->email)) {
                $data['email'] = $container->email;
            }
            
            $form->bind($data);
        }
        
        return new ViewModel([
                'form'=>$form,
                'payByChequeRoute' => $this->url()->fromRoute('lpa/payment', ['lpa-id'=>$this->getLpa()->id], ['query'=>['pay-by-cheque'=>true]]),
        ]);
        
    }
    
    public function successAction()
    {
        $paymentService = $this->getServiceLocator()->get('Payment');
        
        $params = $this->getSuccessParams();
        
        $lpa = $this->getLpa();
        
        $paymentService->verifyMacString($params, $lpa->id);
        $paymentService->verifyOrderKey($params, $lpa->id);
        
        $paymentService->updateLpa($params, $lpa);
        
        // send email
        $communicationService = $this->getServiceLocator()->get('Communication');
        $communicationService->sendInstrumentCompleteEmail($this->getLpa(), $this->url()->fromRoute('lpa/created', ['lpa-id' => $lpa->id], ['force_canonical' => true]));
        
        return $this->redirect()->toRoute('lpa/complete', ['lpa-id'=>$this->getLpa()->id]);
        
        return $this->getResponse();
    }
    
    /**
     * Helper function to verify and extract the success params
     * 
     * @return array
     */
    private function getSuccessParams()
    {
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
        
        return $params;
    }
    
    public function failureAction()
    {
        return new ViewModel([
                'feeUrl' => $this->url()->fromRoute('lpa/fee', ['lpa-id'=>$this->getLpa()->id]),
                'paymentUrl' => $this->url()->fromRoute('lpa/payment', ['lpa-id'=>$this->getLpa()->id]),
        ]);
    }
    
    public function cancelAction()
    {
        return new ViewModel([
                'feeUrl' => $this->url()->fromRoute('lpa/fee', ['lpa-id'=>$this->getLpa()->id]),
                'paymentUrl' => $this->url()->fromRoute('lpa/payment', ['lpa-id'=>$this->getLpa()->id]),
        ]);
    }
    
    public function pendingAction()
    {
        //@todo:  set flash message before redirecting
        
        return $this->redirect()->toRoute('lpa/complete', ['lpa-id'=>$this->getLpa()->id]);
        
        return $this->getResponse();
    }
    
    /**
     * Helper function to construct the Worldpay redirect URL
     *
     * @param string $baseUrl
     * @param string $lpaId
     * @param Uri $uri
     * @return string
     */
    public function getRedirectUrl($baseUrl)
    {
        $redirectUrl =
            $baseUrl .
                '&successURL=' .  $this->getCallbackEndpoint('success') .
                '&pendingURL=' . $this->getCallbackEndpoint('pending') .
                '&failureURL=' . $this->getCallbackEndpoint('failure') .
                '&cancelURL=' . $this->getCallbackEndpoint('cancel');
    
        return $redirectUrl;
    }
    
    /**
     * Helper function to construct the callback URLs
     *
     * @param string $type
     * @return string
     */
    public function getCallbackEndpoint($type)
    {
        $baseUri = (new ServerUrl())->__invoke(false);
    
        return $baseUri . $this->url()->fromRoute(
            'lpa/payment/return/' . $type,
            ['lpa-id' => $this->getLpa()->id]
        );
    }
}
