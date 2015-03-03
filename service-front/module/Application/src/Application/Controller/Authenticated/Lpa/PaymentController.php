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
        $paymentService = $this->getServiceLocator()->get('Payment');

        $options = $paymentService->getOptions($this->getLpa());
        
        $response = 
            $paymentService
                 ->getGateway()
                 ->purchase($options)
                 ->send();
        
        $paymentGatewayBaseUrl = $response->getData()->reference;
        
        $redirectUrl = $this->getRedirectUrl($paymentGatewayBaseUrl);
        
        $this->redirect()->toUrl($redirectUrl);
    }
    
    public function successAction()
    {
        $paymentService = $this->getServiceLocator()->get('Payment');
        
        $params = $this->getSuccessParams();
        
        $lpa = $this->getLpa();
        
        $paymentService->verifyMacString($params, $lpa->id);
        $paymentService->verifyOrderKey($params, $lpa->id);
        
        $paymentService->updateLpa($params, $lpa);
        
        $this->redirect()->toRoute('lpa/complete', ['lpa-id'=>$this->getLpa()->id]);
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
        
        $this->redirect()->toRoute('lpa/complete', ['lpa-id'=>$this->getLpa()->id]);
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
