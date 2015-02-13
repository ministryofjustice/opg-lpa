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
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Application\Form\Lpa\CertificateProviderForm;

class CertificateProviderController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;
        
        $cp = $this->getLpa()->document->certificateProvider;
        if($cp instanceof CertificateProvider) {
            
            return new ViewModel([
                    'certificateProvider' => [
                            'name' => $cp->name->__toString(),
                            'address' => $cp->address->__toString(),
                    ],
                    'editRoute'  => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id'=>$lpaId] ),
                    'nextRoute'     => $this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] )
            ]);
        }
        else {
            return new ViewModel([ 'addRoute' => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>$lpaId] ) ]);
        }
    }
    
    public function addAction()
    {
        $viewModel = new ViewModel();
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $form = new CertificateProviderForm();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            if($form->isValid()) {
                
                $lpaId = $this->getLpa()->id;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                // persist data
                $cp = new CertificateProvider($form->getModelizedData());
                if(!$this->getLpaApplicationService()->setCertificateProvider($lpaId, $cp)) {
                    throw new \RuntimeException('API client failed to save certificate provider for id: '.$lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return $viewModel;
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        
        $viewModel->form = $form;
        
        return $viewModel;
    }
    
    public function editAction()
    {
        $viewModel = new ViewModel();
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $form = new CertificateProviderForm();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            
            if($form->isValid()) {
                $lpaId = $this->getLpa()->id;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                // persist data
                $cp = new CertificateProvider($form->getModelizedData());
                
                if(!$this->getLpaApplicationService()->setCertificateProvider($lpaId, $cp)) {
                    throw new \RuntimeException('API client failed to update certificate provider for id: '.$lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return $viewModel;
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        else {
            $cp = $this->getLpa()->document->certificateProvider->flatten();
            $form->bind($cp);
        }
        
        $viewModel->form = $form;
        
        return $viewModel;
    }
    
}
