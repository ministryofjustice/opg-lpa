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
use Application\Form\Lpa\WhoAreYouForm;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;

class WhoAreYouController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
//         if($this->getLpa()->whoAreYouAnswered == true) {
//             return new ViewModel();
//         }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = new WhoAreYouForm();
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        if($this->request->isPost()) {
            
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                // persist data
                
                $whoAreYou = new WhoAreYou( $form->formDataModelization($postData) );
                
                if( !$this->getLpaApplicationService()->setWhoAreYou($lpaId, $whoAreYou) ) {
                    throw new \RuntimeException('API client failed to set Who Are You for id: '.$lpaId);
                }
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
