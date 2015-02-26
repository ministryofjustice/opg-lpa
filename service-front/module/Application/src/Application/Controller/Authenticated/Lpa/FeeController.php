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
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Application\Form\Lpa\FeeForm;
use Application\Model\Service\Payment\Calculator;

class FeeController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = new FeeForm($this->getLpa());
        
        if($this->request->isPost()) {
            $post = $this->request->getPost();
            
            // do not validate email when payment method is cheque.
            if($post['method'] == 'cheque') {
                $form->setValidationGroup(
                        'method',
                        'repeatCaseNumber',
                        'reducedFeeReceivesBenefits',
                        'reducedFeeAwardedDamages',
                        'reducedFeeLowIncome', 
                        'reducedFeeUniversalCredit'
                );
            }
            
            // set data for validation
            $form->setData($post);
            
            if($form->isValid()) {
                
                // set payment property into local lpa object.
                $lpa = $this->getLpa();
                
                // create a payment object from form data and assign to lpa
                $lpa->payment = new Payment($form->getModelizedData());
                
                if(!empty($post['repeatCaseNumber'])) {
                    $lpa->repeatCaseNumber = $post['repeatCaseNumber'];
                }
                
                // calculate payment amount and get a payment object
                $payment = Calculator::calculate($lpa);
                
                // if payment amount is an integer larger than 0
                if($payment->amount) {
                    // set time date if payment method is cheque, otherwise unset 
                    // the date so it would be set when payment is done.
                    if($payment->method == Payment::PAYMENT_TYPE_CHEQUE) {
                        $payment->date = new \DateTime();
                    }
                    else {
                        $payment->date = null;
                    }
                }
                else {
                    // if payment amount is not an integer, payment method can not be decided.
                    $payment->method = null;
                    $payment->date = new \DateTime();
                }
                
                // send payment object to the api
                if(!$this->getLpaApplicationService()->setPayment($lpaId, $payment)) {
                    throw new \RuntimeException('API client failed to set payment details for id: '.$lpaId);
                }
                
                // set repeatCaseNumber
                if($this->request->getPost('repeatCaseNumber')) {
                    //@todo set repeat number to the api.
                    
                    
                }
                
                // redirect to payment gateway
                if($this->request->getPost('method') == 'card') {
                    // @todo initialize payment process and redirect
                    
                    
                }
                else {
                    // to complete page
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        else {
            $form->bind(['repeatCaseNumber'=>$this->getLpa()->repeatCaseNumber] + $this->getLpa()->payment->flatten());
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
