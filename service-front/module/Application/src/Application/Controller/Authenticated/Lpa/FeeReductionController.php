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
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class FeeReductionController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\FeeReductionForm');
        $lpa = $this->getLpa();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                // if no applying reduced fee, set payment in LPA including amount.
                switch($form->getData()['reductionOptions']) {
                    case 'reducedFeeReceivesBenefits':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => true,
                            'reducedFeeAwardedDamages'  => true,
                            'reducedFeeLowIncome'       => null,
                            'reducedFeeUniversalCredit' => null,
                        ]);
                        break;
                    case 'reducedFeeUniversalCredit':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => false,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => null,
                            'reducedFeeUniversalCredit' => true,
                        ]);
                        break;
                    case 'reducedFeeLowIncome':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => false,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => true,
                            'reducedFeeUniversalCredit' => null,
                        ]);
                        break;
                    case 'notApply':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => null,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => null,
                            'reducedFeeUniversalCredit' => null,
                        ]);
                        break;
                }
                
                // calculate payment amount and get a payment object
                Calculator::calculate($lpa);
                
                if(!$lpa->payment->amount) {
                    $lpa->payment->date = new \DateTime();
                }
                
                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in FeeReductionController');
                }
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);
            }
        }
        else {
            if($lpa->payment instanceof Payment) {
                if($lpa->payment->reducedFeeReceivesBenefits && $lpa->payment->reducedFeeAwardedDamages) {
                    $reductionOptionsValue = 'reducedFeeReceivesBenefits';
                }
                elseif($lpa->payment->reducedFeeUniversalCredit) {
                    $reductionOptionsValue = 'reducedFeeUniversalCredit';
                }
                elseif($lpa->payment->reducedFeeLowIncome) {
                    $reductionOptionsValue = 'reducedFeeLowIncome';
                }
                else {
                    $reductionOptionsValue = 'notApply';
                }
                
                $form->bind([
                        'reductionOptions' => $reductionOptionsValue,
                ]);
            }
        }
        
        return new ViewModel([
                'form'=>$form, 
        ]);
    }
}
