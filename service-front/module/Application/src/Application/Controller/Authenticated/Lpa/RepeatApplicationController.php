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
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;

class RepeatApplicationController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\RepeatApplicationForm');
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($postData['isRepeatApplication'] != 'is-repeat') {
                $form->setValidationGroup(
                    'isRepeatApplication'
                );
            }
            
            if($form->isValid()) {
                
                $lpaId = $lpa->id;
                $repeatCaseNumber = $lpa->repeatCaseNumber;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                // persist data
                if($form->getData()['isRepeatApplication'] == 'is-repeat') {
                    
                    // set repeat case number only if case number changed or added
                    if($form->getData()['repeatCaseNumber'] != $lpa->repeatCaseNumber) {
                        if(!$this->getLpaApplicationService()->setRepeatCaseNumber($lpa->id, $form->getData()['repeatCaseNumber'])) {
                            throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpaId);
                        }
                    }
                    
                    $lpa->repeatCaseNumber = $form->getData()['repeatCaseNumber'];
                }
                else {
                    if($lpa->repeatCaseNumber !== null) {
                        // delete case number if it has been set previousely.
                        if(!$this->getLpaApplicationService()->deleteRepeatCaseNumber($lpa->id)) {
                            throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpaId);
                        }
                    }
                    
                    $lpa->repeatCaseNumber = null;
                }
                
                if(($lpa->payment instanceof Payment) && ($lpa->repeatCaseNumber != $repeatCaseNumber)) {
                    Calculator::calculate($lpa);
                    
                    if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                        throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in FeeReductionController');
                    }
                }
                
                // set metadata
                $this->getServiceLocator()->get('Metadata')->setRepeatApplicationConfirmed($lpa);
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            if(array_key_exists(Metadata::REPEAT_APPLICATION_CONFIRMED, $lpa->metadata)) {
                $form->bind([
                        'isRepeatApplication' => ($lpa->repeatCaseNumber === null)?'is-new':'is-repeat',
                        'repeatCaseNumber'    => $lpa->repeatCaseNumber,
                        
                ]);
            }
        }
        
        return new ViewModel([
                'form'=>$form,
        ]);
    }
}
