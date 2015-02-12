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
use Application\Form\Lpa\HowPrimaryAttorneysMakeDecisionForm;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class HowPrimaryAttorneysMakeDecisionController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $form = new HowPrimaryAttorneysMakeDecisionForm();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpaId = $this->getLpa()->id;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                $howAttorneyAct = $form->get('how')->getValue();
                
                if($this->getLpa()->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                    $decision = $this->getLpa()->document->primaryAttorneyDecisions;
                }
                else {
                    $decision = new PrimaryAttorneyDecisions();
                }
                
                $decision->how = $howAttorneyAct;
                
                if($howAttorneyAct == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                    $decision->howDetails = $form->get('howDetails')->getValue();
                }
                
                // persist data
                if(!$this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpaId, $decision)) {
                    echo $this->getLpaApplicationService()->getLAstContent();
                    throw new \RuntimeException('API client failed to set primary attorney decisions for id: '.$lpaId);
                }
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            $form->bind($this->getLpa()->document->primaryAttorneyDecisions->flatten());
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
