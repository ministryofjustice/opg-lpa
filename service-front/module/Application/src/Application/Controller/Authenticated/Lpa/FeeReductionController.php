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
use Application\Model\Service\Lpa\Metadata;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class FeeReductionController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\FeeReductionForm');
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpaId = $this->getLpa()->id;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                // if no applying reduced fee, set payment in LPA including amount.
                if($form->getData()['applyForFeeReduction'] === '0')  {
                    $lpa = $this->getLpa();
                    
                    $payment = new Payment();
                    
                    $lpa->payment = $payment;
                    
                    // calculate payment amount and get a payment object
                    Calculator::calculate($lpa);
                    
                    if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                        throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in FeeReductionController');
                    }
                }
                
                $this->getServiceLocator()->get('Metadata')->setApplyForFeeReduction($this->getLpa(), (bool)$form->getData()['applyForFeeReduction']);
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            if(array_key_exists(Metadata::APPLY_FOR_FEE_REDUCTION, $this->getLpa()->metadata)) {
                $form->bind(['applyForFeeReduction' => $this->getLpa()->metadata[Metadata::APPLY_FOR_FEE_REDUCTION]]);
            }
        }
        
        return new ViewModel([
                'form'=>$form, 
        ]);
    }
}
