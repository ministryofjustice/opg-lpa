<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;
use Application\Model\FormFlowChecker;

class TypeController extends AbstractAuthenticatedController
{
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TypeForm');
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                $lpaId = $this->getLpaApplicationService()->createApplication();
                
                if( $lpaId === false ){
                
                    $this->flashMessenger()->addErrorMessage('Error creating a new LPA. Please try again.');
                    return $this->redirect()->toRoute( 'user/dashboard' );
                
                }
                
                $lpaType = $form->getData()['type'];
                
                // persist data
                if(!$this->getLpaApplicationService()->setType($lpaId, $lpaType)) {
                    throw new \RuntimeException('API client failed to set LPA type for id: '.$lpaId);
                }
                
                $formFlowChecker = new FormFlowChecker();
                return $this->redirect()->toRoute($formFlowChecker->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        
        $analyticsDimensions = [
            'dimension2' => date('Y-m-d'),
            'dimension3' => 0,
        ];
    
        $this->layout()->contentHeader = $this->contentHeader;
        $this->layout()->setVariable('analyticsDimensions', json_encode($analyticsDimensions));
        
        $view = new ViewModel([
            'form'=>$form, 
        ]);
        
        $view->setTemplate('application/type/index.phtml');
        
        return $view;
    }

}
