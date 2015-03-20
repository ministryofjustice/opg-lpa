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
use Zend\Session\Container;

class FeeController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = new FeeForm($lpa);
        
        if($this->request->isPost()) {
            $post = $this->request->getPost();
            
            // set data for validation
            $form->setData($post);
            
            // set payment property into local lpa object.
            // create a payment object from form data and assign to lpa
            $payment = new Payment([
                    'reducedFeeReceivesBenefits' => (bool)$post['reducedFeeReceivesBenefits'],
                    'reducedFeeAwardedDamages'  => (bool)$post['reducedFeeAwardedDamages'],
                    'reducedFeeLowIncome'       => (bool)$post['reducedFeeLowIncome'],
                    'reducedFeeUniversalCredit' => (bool)$post['reducedFeeUniversalCredit'],
            ]);
            
            $lpa->payment = $payment;
            
            if(!empty($post['repeatCaseNumber'])) {
                $lpa->repeatCaseNumber = $post['repeatCaseNumber'];
            }
            
            // calculate payment amount and get a payment object
            Calculator::calculate($lpa);
            
            // set payment method only when amount is larger than 0
            if($lpa->payment->amount > 0) {
                $lpa->payment->method = $post['method'];
            }
            else {
                $lpa->payment->method = null;
            }
            
            // do not validate email when payment method is cheque.
            if(($post['method'] == 'cheque')||(!$lpa->payment->amount)) {
                $form->setValidationGroup(
                        'method',
                        'repeatCaseNumber',
                        'reducedFeeReceivesBenefits',
                        'reducedFeeAwardedDamages',
                        'reducedFeeLowIncome',
                        'reducedFeeUniversalCredit'
                );
            }
            
            if($form->isValid()) {
                
                if($lpa->payment->amount) {
                    // set time date if payment method is cheque, otherwise unset 
                    // the date so it would be set when payment is done.
                    if($lpa->payment->method == Payment::PAYMENT_TYPE_CHEQUE) {
                        $lpa->payment->date = new \DateTime();
                    }
                    else {
                        $lpa->payment->date = null;
                    }
                }
                else {
                    // if payment amount is 0 or null, payment method can not be decided or no need to pay.
                    $lpa->payment->method = null;
                    $lpa->payment->date = new \DateTime();
                }
                
                // send payment object to the api
                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id);
                }
                
                // set repeatCaseNumber
                if($form->get('repeatCaseNumber')->getValue()) {
                    if(!$this->getLpaApplicationService()->setRepeatCaseNumber($lpa->id, $form->get('repeatCaseNumber')->getValue())) {
                        throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpa->id);
                    }
                }
                
                // redirect to payment gateway
                if($lpa->payment->method == Payment::PAYMENT_TYPE_CARD) {
                    
                    // set paymentEmail in session container.
                    $container = new Container('paymentEmail');
                    $container->email = $form->getData()['email'];
                    
                    $this->redirect()->toRoute('lpa/payment', ['lpa-id' => $lpa->id]);
                }
                else {
                    // to complete page
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);
                }
            }
        }
        else {
            $data = ['repeatCaseNumber'=>$lpa->repeatCaseNumber];
            
            if($lpa->payment instanceof Payment) {
                $data +=  $this->getLpa()->payment->flatten();
            }
            
            $container = new Container('paymentEmail');
            if(isset($container->email)) {
                $data['email'] = $container->email;
            }
            
            $form->bind($data);
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
