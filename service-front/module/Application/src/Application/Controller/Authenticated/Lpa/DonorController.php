<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Zend\View\Model\JsonModel;
use Application\Model\Service\Lpa\Communication;

class DonorController extends AbstractLpaActorController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $viewModel = new ViewModel();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $lpaId = $this->getLpa()->id;
        
        $donor = $this->getLpa()->document->donor;
        if( $donor instanceof Donor ) {
            
            return new ViewModel([
                    'donor'         => [
                            'name'  => $donor->name,
                            'address' => $donor->address,
                    ],
                    'editDonorUrl'  => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id'=>$lpaId] ),
                    'nextRoute'     => $this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] )
            ]);
        }
        else {
            return new ViewModel([ 'addRoute' => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>$lpaId] ) ]);
        }
        
    }
    
    public function addAction()
    {
        $lpaId = $this->getLpa()->id;
        $routeMatch = $this->getEvent()->getRouteMatch();
        
        if( $this->getLpa()->document->donor instanceof Donor ) {
            return $this->redirect()->toRoute('lpa/donor', ['lpa-id'=>$lpaId]);
        }
        
        $viewModel = new ViewModel(['routeMatch' => $routeMatch]);
        $viewModel->setTemplate('application/donor/form.twig');
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\DonorForm');
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));
        
        $seedSelection = $this->seedDataSelector($viewModel, $form);
        if($seedSelection instanceof JsonModel) {
            return $seedSelection;
        }
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // received POST from donor form submission
            if(!$postData->offsetExists('pick-details')) {
                
                // handle donor form submission
                $form->setData($postData);
                if($form->isValid()) {
                    
                    // persist data
                    $donor = new Donor($form->getModelDataFromValidatedForm());
                    if(!$this->getLpaApplicationService()->setDonor($lpaId, $donor)) {
                        throw new \RuntimeException('API client failed to save LPA donor for id: '.$lpaId);
                    }
                    
                    if ( $this->getRequest()->isXmlHttpRequest() ) {
                        return new JsonModel(['success' => true]);
                    }
                    else {
                        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName()), ['lpa-id' => $lpaId]);
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
            $viewModel->useMyDetailsRoute = $this->url()->fromRoute('lpa/donor/add', ['lpa-id' => $lpaId]) . '?use-my-details=1';
        }
        
        return $viewModel;
    }
    
    public function editAction()
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $viewModel = new ViewModel(['routeMatch' => $routeMatch]);
        
        $viewModel->setTemplate('application/donor/form.twig');
        
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $routeMatch->getMatchedRouteName();
        
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\DonorForm');
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $postData['canSign'] = (bool) $postData['canSign'];
            
            $form->setData($postData);
            
            if($form->isValid()) {
                
                // persist data
                $donor = new Donor($form->getModelDataFromValidatedForm());
                
                if(!$this->getLpaApplicationService()->setDonor($lpaId, $donor)) {
                    throw new \RuntimeException('API client failed to update LPA donor for id: '.$lpaId);
                }
                
                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return new JsonModel(['success' => true]);
                }
                else {
                    return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        else {
            $donor = $this->getLpa()->document->donor->flatten();
            $dob = $this->getLpa()->document->donor->dob->date;
            $donor['dob-date'] = [
                        'day'   => $dob->format('d'),
                        'month' => $dob->format('m'),
                        'year'  => $dob->format('Y'),
            ];
            $form->bind($donor);
        }
        
        $viewModel->form = $form;
        
        return $viewModel;
    }
}
