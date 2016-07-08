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
use Zend\Form\Element;

class FeeReductionController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\FeeReductionForm');
        $lpa = $this->getLpa();
        
        $reduction = $form->get('reductionOptions');
        
        $reductionOptions = [];
        
        $reductionOptions['receivesBenefits'] = new Element('who', [
            'label' => "The donor currently claims one of <a class=\"js-guidance\" href=\"/help/#topic-fees-and-reductions\" data-journey-click=\"stageprompt.lpa:help:fees-and-reductions\">these benefits</a>, but has not been awarded personal injury damages of more than £16,000
        <div class=\"revised-fee hidden\" id=\"revised-fee-0\">
        	<h2>Reduced fee: &pound;0</h2>
            <p>To apply for this exemption, you must enclose copies of letters from the Department for Work and Pensions or your benefit provider. The 
        letters must confirm that the benefit was being paid at the time the LPA was sent to us for registration. <a class=\"js-guidance\" href=\"/help/#topic-fees-and-reductions\" data-journey-click=\"stageprompt.lpa:help:fees-and-reductions\">Find out more about acceptable proof</a>.</p>
            
            <p>If you don't send the right evidence, your application will be delayed. If your claim is rejected, you'll need to pay the rest of the fee.</p>
            
            <p>On the next page, you can download the application for remission form with the application to register form.
            Sign and date this and include it in your application.</p>
        </div>",
        ]);
        $reductionOptions['receivesBenefits']->setAttributes([
            'type' => 'radio',
            'id' => 'reducedFeeReceivesBenefits',
            'value' => $reduction->getOptions()['value_options']['receivesBenefits']['value'],
            'checked' => (($reduction->getValue() == 'receivesBenefits')? 'checked':null),
        ]);
        
        $reductionOptions['reducedFeeUniversalCredit'] = new Element('who', [
            'label' => "The donor receives Universal Credit
        <div class=\"revised-fee hidden\" id=\"revised-fee-uc\">
        	<h2>We'll contact you about the fee</h2>
        	<p>Because Universal Credit (UC) is in its trial phase and replaces several existing benefits, we're looking at remissions on a case-by-case basis.</p>
        	<p>This means <strong>the tool will not charge you for this LPA now.</strong></p>
        	<p>You must still send us the remissions form that you'll download on the next page, along with supporting evidence for your application. This should be a copy of the donor's benefit award letter. </p>
        	<p>Once we receive your application, we'll contact you to let you know how much you'll have to pay and to arrange payment.</p>
        </div>",
        ]);
        $reductionOptions['reducedFeeUniversalCredit']->setAttributes([
            'type' => 'radio',
            'id' => 'reducedFeeUniversalCredit',
            'value' => $reduction->getOptions()['value_options']['reducedFeeUniversalCredit']['value'],
            'checked' => (($reduction->getValue() == 'reducedFeeUniversalCredit')? 'checked':null),
        ]);
        
        $reductionOptions['reducedFeeLowIncome'] = new Element('who', [
            'label' => "The donor currently has an income of less than £12,000 a year before tax",
        ]);
        $reductionOptions['reducedFeeLowIncome']->setAttributes([
            'type' => 'radio',
            'id' => 'reducedFeeLowIncome',
            'value' => $reduction->getOptions()['value_options']['reducedFeeLowIncome']['value'],
            'checked' => (($reduction->getValue() == 'reducedFeeLowIncome')? 'checked':null),
        ]);
        
        $reductionOptions['notApply'] = new Element('who', [
            'label' => "I'm not applying for a reduced fee",
        ]);
        $reductionOptions['notApply']->setAttributes([
            'type' => 'radio',
            'id' => 'notApply',
            'value' => $reduction->getOptions()['value_options']['notApply']['value'],
            'checked' => (($reduction->getValue() == 'notApply')? 'checked':null),
        ]);
        
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
                        // payment date will be set by the API when setPayment() is called.
                        break;
                    case 'reducedFeeUniversalCredit':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => false,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => false,
                            'reducedFeeUniversalCredit' => true,
                        ]);
                        // payment date will be set by the API when setPayment() is called.
                        break;
                    case 'reducedFeeLowIncome':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => false,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => true,
                            'reducedFeeUniversalCredit' => false,
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
                
                // calculate payment amount and set payment in LPA
                Calculator::calculate($lpa);
                
                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in FeeReductionController');
                }
                
                if(!$lpa->payment->amount) {
                    $communicationService = $this->getServiceLocator()->get('Communication');
                    $communicationService->sendRegistrationCompleteEmail($this->getLpa(), $this->url()->fromRoute('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true]));
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
                'reductionOptions' => $reductionOptions,
        ]);
    }
}
