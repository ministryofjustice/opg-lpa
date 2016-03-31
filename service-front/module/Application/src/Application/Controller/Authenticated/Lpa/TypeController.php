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
use Opg\Lpa\DataModel\Lpa\Document\Document;

class TypeController extends AbstractLpaController
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
                
                $lpaId = $this->getLpa()->id;
                
                $lpaType = $form->getData()['type'];
                
                if($lpaType != $this->getLpa()->document->type) {
                    // persist data
                    if(!$this->getLpaApplicationService()->setType($lpaId, $lpaType)) {
                        throw new \RuntimeException('API client failed to set LPA type for id: '.$lpaId);
                    }
                }
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            if($this->getLpa()->document instanceof Document) {
                $form->bind($this->getLpa()->document->flatten());
            }
        }
        
        if (empty($this->getLpa()->document->type)) {
            $analyticsDimensions = [
                'dimension2' => date('Y-m-d'),
                'dimension3' => 0,
            ];
        
            $this->layout()->setVariable('analyticsDimensions', json_encode($analyticsDimensions));
        
        }
        
        return new ViewModel([
                'form'=>$form, 
                'cloneUrl'=>$this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id'=>$this->getLpa()->id]),
                'nextUrl'=>$this->url()->fromRoute('lpa/donor', ['lpa-id'=>$this->getLpa()->id]),
        ]);
    }

}
