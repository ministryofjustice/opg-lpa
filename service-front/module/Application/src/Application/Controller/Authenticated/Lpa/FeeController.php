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
use Zend\InputFilter\InputFilter;

class FeeController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = new FeeForm();
        
        if($this->request->isPost()) {
            $post = $this->request->getPost();
            
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
                
                // persist fee remission data
                $payment = new Payment($form->getModelizedData());
                if(!$this->getLpaApplicationService()->setPayment($lpaId, $payment)) {
                    throw new \RuntimeException('API client failed to set payment details for id: '.$lpaId);
                }
                
                //@todo  set repeatCaseNumber
                if($this->request->getPost('repeatCaseNumber')) {
                    
                }
                
                if($this->request->getPost('method') == 'card') {
                    //redirect to payment gateway
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
