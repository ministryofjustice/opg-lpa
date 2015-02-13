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
use Application\Form\Lpa\InstructionsAndPreferencesForm;

class InstructionsController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = new InstructionsAndPreferencesForm();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                // persist data
                if(!$this->getLpaApplicationService()->setInstructions($lpaId, $form->get('instruction')->getValue())) {
                    throw new \RuntimeException('API client failed to set LPA instructions for id: '.$lpaId);
                }
                
                if(!$this->getLpaApplicationService()->setPreferences($lpaId, $form->get('preference')->getValue())) {
                    throw new \RuntimeException('API client failed to set LPA preferences for id: '.$lpaId);
                }
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            $form->bind($this->getLpa()->document->flatten());
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
