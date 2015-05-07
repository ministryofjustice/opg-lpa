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

class RepeatApplicationController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\RepeatApplicationForm');
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpaId = $this->getLpa()->id;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                // persist data
                if($form->getData()['isRepeatApplication'] == 'is-repeat') {
                    if(!$this->getLpaApplicationService()->setRepeatCaseNumber($this->getLpa()->id, $form->getData()['repeatCaseNumber'])) {
                        throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpaId);
                    }
                }
                elseif($this->getLpa()->repeatCaseNumber !== null) {
                    // delete case number if it has been set previousely.
                    if(!$this->getLpaApplicationService()->deleteRepeatCaseNumber($this->getLpa()->id)) {
                        throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpaId);
                    }
                }
                
                // set metadata
                $this->getServiceLocator()->get('Metadata')->setRepeatApplicationConfirmed($this->getLpa());
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            if(array_key_exists(Metadata::REPEAT_APPLICATION_CONFIRMED, $this->getLpa()->metadata)) {
                $form->bind([
                        'isRepeatApplication' => ($this->getLpa()->repeatCaseNumber === null)?'is-new':'is-repeat',
                        'repeatCaseNumber'    => $this->getLpa()->repeatCaseNumber,
                        
                ]);
            }
        }
        
        return new ViewModel([
                'form'=>$form,
        ]);
    }
}
