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

class BenefitsController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\BenefitsForm');
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // only validate reducedFeeAwardedDamages when 'donor receiving benefits' option is ticked
            if($postData['reducedFeeReceivesBenefits'] != 1) {
                $form->setValidationGroup(
                        'reducedFeeReceivesBenefits'
                );
            }
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpa = $this->getLpa();
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                if($form->getData()['reducedFeeReceivesBenefits']) {
                    if($form->getData()['reducedFeeAwardedDamages']) {
                        // has no damage award, or damage award less than 16k, no payment required
                        $lpa->payment = new Payment([
                                'reducedFeeReceivesBenefits' => true,
                                'reducedFeeAwardedDamages'   => true,
                                'amount'                     => 0,
                                'date'                       => new \DateTime(),
                        ]);
                    }
                    else {
                        // damage award over 16k, immediate payment payment maybe required
                        $lpa->payment = new Payment([
                                'reducedFeeReceivesBenefits' => true,
                                'reducedFeeAwardedDamages'   => false,
                        ]);
                    }
                }
                else {
                    // not receives benefits, immediate payment maybe required.
                    $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => false,
                    ]);
                }
                
                // persist data
                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set benefits in payment for id: '.$lpa->id);
                }
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);
            }
        }
        else {
            if($this->getLpa()->payment instanceof Payment) {
                $form->bind([
                        'reducedFeeReceivesBenefits' => $this->getLpa()->payment->reducedFeeReceivesBenefits,
                        'reducedFeeAwardedDamages'   => $this->getLpa()->payment->reducedFeeAwardedDamages,
                ]);
            }
        }
        
        return new ViewModel([
                'form'=>$form, 
        ]);
    }
}
