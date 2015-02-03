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
use Application\Form\DonorForm;

class DonorController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        return new ViewModel();
    }
    
    public function addAction()
    {
        $form = new DonorForm();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            
            if($form->isValid()) {
                
//                 $this->redirect('lpa/donor', ['lpa-id'=>$this->request->getPost('lpa-id')]);
            }
        }
        
        $viewModel = new ViewModel(['form'=>$form]);
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        return $viewModel;
    }
    
    public function editAction()
    {
        $form = new DonorForm();
        
        $form->bind(new \ArrayObject($this->getLpa()->document->donor->flatten()));
        
        if($this->request->isPost()) {
            
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            
            if($form->isValid()) {
//                 $this->redirect('lpa/donor', ['lpa-id'=>$this->request->getPost('lpa-id')]);
            }
        }
        
        $viewModel = new ViewModel(['form'=>$form]);
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        return $viewModel;
    }
}
