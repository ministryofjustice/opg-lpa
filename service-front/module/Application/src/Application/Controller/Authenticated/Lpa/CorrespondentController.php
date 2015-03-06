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
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Application\Form\Lpa\CorrespondentForm;
use Application\Form\Lpa\CorrespondentSwitcherForm;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\User\Address;

class CorrespondentController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $viewModel = new ViewModel();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $lpaId = $this->getLpa()->id;
        
        $correspondent = $this->getLpa()->document->correspondent;
        if( $correspondent instanceof Correspondence ) {
            
            return new ViewModel([
                    'correspondent'     => [
                            'name'      => (($correspondent->name instanceof Name)?$correspondent->name->__toString():$correspondent->company),
                            'address'   => $correspondent->address->__toString(),
                    ],
                    'editRoute'     => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id'=>$lpaId] ),
                    'nextRoute'     => $this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] )
            ]);
        }
        else {
            throw new \RuntimeException('LPA Correspondent should not be null.');
        }
    }
    
    public function editAction()
    {
        $viewModel = new ViewModel();
        
        if ( $this->getRequest()->isXmlHttpRequest() ) {
            $viewModel->setTerminal(true);
        }
        
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $correspondentForm = new CorrespondentForm();
        $correspondentForm->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        $switcherForm = new CorrespondentSwitcherForm($this->getLpa());
        $switcherForm->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            if($postData->offsetExists('switch-to-type')) {
                $switcherForm->setData($postData);
                if($switcherForm->isValid()) {
                    switch($postData['switch-to-type']) {
                        case 'me':
                            $userSession = $this->getServiceLocator()->get('UserDetailsSession');
                            
                            $params = [
                                    'who'=>'other',
                                    'name-title' => $userSession->user->name->title,
                                    'name-first' => $userSession->user->name->first,
                                    'name-last' => $userSession->user->name->last,
                            ];
                            if($userSession->user->address instanceof Address) {
                                $params += [
                                        'address-address1' => $userSession->user->address->address1,
                                        'address-address2' => $userSession->user->address->address2,
                                        'address-address3' => $userSession->user->address->address3,
                                        'address-postcode' => $userSession->user->address->postcode,
                                ];
                            }
                            $correspondentForm->bind($params);
                            break;
                        case 'donor':
                            $correspondent = $this->getLpa()->document->donor->flatten();
                            $correspondent['who'] = 'donor';
                            $correspondentForm->bind($correspondent);
                            break;
                        default:
                            if(is_numeric($postData['switch-to-type'])) {
                                foreach($this->getLpa()->document->primaryAttorneys as $attorney) {
                                    if($attorney->id == $postData['switch-to-type']) {
                                        $correspondent = $attorney->flatten();
                                        if($attorney instanceof TrustCorporation) {
                                            $correspondent['company'] = $attorney->name;
                                        }
                                        $correspondent['who'] = 'attorney';
                                        $correspondentForm->bind($correspondent);
                                        break;
                                    }
                                }
                            }
                            else {
                                $correspondentForm->bind(['who'=>'other']);
                            }
                            break;
                    }
                }
            }
            else {
                $correspondentForm->setData($postData);
                if($correspondentForm->isValid()) {
                    
                    // persist data
                    $correspondent = new Correspondence($correspondentForm->getModelizedData());
                    
                    if(!$this->getLpaApplicationService()->setCorrespondent($lpaId, $correspondent)) {
                        throw new \RuntimeException('API client failed to update correspondent for id: '.$lpaId);
                    }
                    
                    if ( $this->getRequest()->isXmlHttpRequest() ) {
                        return $this->response;
                    }
                    else {
                        $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                    }
                }
            }
        }
        else {
            $correspondent = $this->getLpa()->document->correspondent->flatten();
            $correspondentForm->bind($correspondent);
        }
        
        $viewModel->correspondentForm = $correspondentForm;
        $viewModel->switcherForm = $switcherForm;
        
        return $viewModel;
    }
}
