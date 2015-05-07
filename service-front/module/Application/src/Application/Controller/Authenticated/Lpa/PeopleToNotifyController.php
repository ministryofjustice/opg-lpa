<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Zend\View\Model\JsonModel;
use Application\Model\Service\Lpa\Metadata;
use Application\Controller\AbstractLpaActorController;

class PeopleToNotifyController extends AbstractLpaActorController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;
        
        // set hidden form for saving empty array to peopleToNotify.
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\BlankForm');
        
        if($this->request->isPost()) {
        
            $form->setData($this->request->getPost());
        
            if($form->isValid()) {
                
                // set user has confirmed if there are people to notify
                $this->getServiceLocator()->get('Metadata')->setPeopleToNotifyConfirmed($this->getLpa());
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        
        // list notified persons on the landing page if they've been added.
        $peopleToNotifyParams = [];
        foreach($this->getLpa()->document->peopleToNotify as $idx=>$peopleToNotify) {
            $peopleToNotifyParams[] = [
                    'notifiedPerson' => [
                            'name'      => $peopleToNotify->name,
                            'address'   => $peopleToNotify->address
                    ],
                    'editRoute'     => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id' => $lpaId, 'idx' => $idx ]),
                    'deleteRoute'   => $this->url()->fromRoute( $currentRouteName.'/delete', ['lpa-id' => $lpaId, 'idx' => $idx ]),
            ];
        }
        
        $view = new ViewModel(['form' => $form, 'peopleToNotify' => $peopleToNotifyParams]);
        
        if( count($this->getLpa()->document->peopleToNotify) < 5) {
            $view->addRoute  = $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id' => $lpaId] );
        }
        
        return $view;
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

        if(count($this->getLpa()->document->peopleToNotify) >= 5 ) {
            return $this->redirect()->toRoute('lpa/people-to-notify', ['lpa-id'=>$lpaId]);
        }
        
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PeopleToNotifyForm');
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        $seedSelection = $this->seedDataSelector($viewModel, $form);
        if($seedSelection instanceof JsonModel) {
            return $seedSelection;
        }
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // received a POST from the peopleToNotify form submission
            if(!$postData->offsetExists('pick-details')) {
                
                // handle notified person form submission
                $form->setData($postData);
                if($form->isValid()) {
                    
                    // persist data
                    $np = new NotifiedPerson($form->getModelDataFromValidatedForm());
                    if(!$this->getLpaApplicationService()->addNotifiedPerson($lpaId, $np)) {
                        throw new \RuntimeException('API client failed to add a notified person for id: '.$lpaId);
                    }
                    
                    // remove metadata flag value if exists
                    if(!array_key_exists(Metadata::PEOPLE_TO_NOTIFY_CONFIRMED, $this->getLpa()->metadata)) {
                            $this->getServiceLocator()->get('Metadata')->setPeopleToNotifyConfirmed($this->getLpa());
                    }
                    
                    // redirect to next page for non-js, or return a json to ajax call.
                    if ( $this->getRequest()->isXmlHttpRequest() ) {
                        return new JsonModel(['success' => true]);
                    }
                    else {
                        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                    }
                }
            }
        }
        else {
            // load user's details into the form
            if($this->params()->fromQuery('use-my-details')) {
                $form->bind($this->getUserDetailsAsArray());
            }
        }
        
        $viewModel->form = $form;
        
        // show user my details link (if the link has not been clicked and seed dropdown is not set in the view)
        if(($viewModel->seedDetailsPickerForm==null) && !$this->params()->fromQuery('use-my-details')) {
            $viewModel->useMyDetailsRoute = $this->url()->fromRoute('lpa/people-to-notify/add', ['lpa-id' => $lpaId]) . '?use-my-details=1';
        }
        
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
        if(array_key_exists($personIdx, $this->getLpa()->document->peopleToNotify)) {
            $notifiedPerson = $this->getLpa()->document->peopleToNotify[$personIdx];
        }
        
        // if notified person idx does not exist in lpa, return 404.
        if(!isset($notifiedPerson)) {
            return $this->notFoundAction();
        }
        
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PeopleToNotifyForm');
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
                
                // redirect to next page for non-js, or return a json to ajax call.
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return new JsonModel(['success' => true]);
                }
                else {
                    return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
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
        
        if(array_key_exists($personIdx, $this->getLpa()->document->peopleToNotify)) {
            
            // persist data to the api
            if(!$this->getLpaApplicationService()->deleteNotifiedPerson($lpaId, $this->getLpa()->document->peopleToNotify[$personIdx]->id)) {
                throw new \RuntimeException('API client failed to delete notified person ' . $personIdx . ' for id: ' . $lpaId);
            }
            
        }
        else {
            // if notified person idx does not exist in lpa, return 404.
            return $this->notFoundAction();
        }
        
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            return new JsonModel(['success' => true]);
        }
        else {
            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
            return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
        }
    }
}
