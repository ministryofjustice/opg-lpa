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
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Application\Form\Lpa\PeopleToNotifyForm;
use Zend\View\Model\JsonModel;
use Zend\Form\Form;
use Zend\Form\Element\Csrf;

class PeopleToNotifyController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;
        
        // set hidden form for saving empty array to peopleToNotify.
        $form = new Form();
        $form->setAttribute('method', 'post');
        
        $form->add( (new Csrf('secret'))->setCsrfValidatorOptions([
                'timeout' => null,
                'salt' => sha1('Application\Form\Lpa-Salt'),
        ]));
        
        if($this->request->isPost()) {
        
            $form->setData($this->request->getPost());
        
            if($form->isValid()) {
                
                // check if peopleToNotify is empty. If yes, save an empty array to LPA peopleToNotify property, so we know user has no peopleToNotify to be added into LPA.
                if($this->getLpa()->document->peopleToNotify === null) {
                    // @todo to be completed after datamodel updated
                    if( !$this->getLpaApplicationService()->setNotifiedPersons($lpaId, []) ) {
                       throw new \RuntimeException('API client failed to add a people to notify for id: '.$lpaId);
                    }
                }
                
               $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        
        if( is_array($this->getLpa()->document->peopleToNotify) && ( count($this->getLpa()->document->peopleToNotify) > 0 )) {
            
            $peopleToNotify = [];
            foreach($this->getLpa()->document->peopleToNotify as $idx=>$peopleToNotify) {
                $peopleToNotifyParams[] = [
                        'notifiedPerson' => [
                                'name'      => $peopleToNotify->name->__toString(),
                                'address'   => $peopleToNotify->address->__toString()
                        ],
                        'editRoute'     => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id' => $lpaId, 'idx' => $idx ]),
                        'deleteRoute'   => $this->url()->fromRoute( $currentRouteName.'/delete', ['lpa-id' => $lpaId, 'idx' => $idx ]),
                ];
            }
            
            $view = new ViewModel([
                    'form'           => $form,
                    'peopleToNotify' => $peopleToNotifyParams
            ]);
            
            if( count($this->getLpa()->document->peopleToNotify) < 5) {
                $view->addRoute  = $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id' => $lpaId] );
            }
            
            return $view;
        }
        else {
            
            return new ViewModel([
                    'form'      => $form,
                    'addRoute'  => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>$lpaId] ),
                    'nextRoute' => $this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] ),
            ]);
            
        }
    }
    
    public function addAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/people-to-notify/form.phtml');
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        if(is_array($this->getLpa()->document->peopleToNotify) && ( count($this->getLpa()->document->peopleToNotify) >= 5 )) {
            $this->redirect()->toRoute('lpa/people-to-notify', ['lpa-id'=>$lpaId]);
        }
        
        $form = new PeopleToNotifyForm();
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);
            if($form->isValid()) {
                
                // persist data
                $np = new NotifiedPerson($form->getModelDataFromValidatedForm());
                if(!$this->getLpaApplicationService()->addNotifiedPerson($lpaId, $np)) {
                    throw new \RuntimeException('API client failed to add a notified person for id: '.$lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return new JsonModel(['success' => true]);
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
        $viewModel->setTemplate('application/people-to-notify/form.phtml');
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $personIdx = $this->getEvent()->getRouteMatch()->getParam('idx');
        if(is_array($this->getLpa()->document->peopleToNotify) && array_key_exists($personIdx, $this->getLpa()->document->peopleToNotify)) {
            $notifiedPerson = $this->getLpa()->document->peopleToNotify[$personIdx];
        }
        
        // if notified person idx does not exist in lpa, return 404.
        if(!isset($notifiedPerson)) {
            return $this->notFoundAction();
        }
        
        $form = new PeopleToNotifyForm();
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId, 'idx'=>$personIdx]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);
            
            if($form->isValid()) {
                // update details
                $notifiedPerson->populate($form->getModelDataFromValidatedForm());
                
                // persist to the api
                if(!$this->getLpaApplicationService()->setNotifiedPerson($lpaId, $notifiedPerson, $notifiedPerson->id)) {
                    throw new \RuntimeException('API client failed to update notified person ' . $personIdx . ' for id: ' . $lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return new JsonModel(['success' => true]);
                }
                else {
                    $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        else {
            $form->bind($notifiedPerson->flatten());
        }
        
        $viewModel->form = $form;
        
        return $viewModel;
    }
    
    public function deleteAction()
    {
        $lpaId = $this->getLpa()->id;
        $personIdx = $this->getEvent()->getRouteMatch()->getParam('idx');
        
        $deletionFlag = true;
        if(is_array($this->getLpa()->document->peopleToNotify) && array_key_exists($personIdx, $this->getLpa()->document->peopleToNotify)) {
            if(!$this->getLpaApplicationService()->deleteNotifiedPerson($lpaId, $this->getLpa()->document->peopleToNotify[$personIdx]->id)) {
                throw new \RuntimeException('API client failed to delete notified person ' . $personIdx . ' for id: ' . $lpaId);
            }
            $deletionFlag = true;
        }
        
        // if notified person idx does not exist in lpa, return 404.
        if(!$deletionFlag) {
            return $this->notFoundAction();
        }
        
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            return new JsonModel(['success' => true]);
        }
        else {
            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
            $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
        }
    }
}
