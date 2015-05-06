<?php
namespace Application\Controller;

use RuntimeException;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;
use Application\Form\Lpa\AbstractActorForm;
use Opg\Lpa\DataModel\User\Dob;
use Opg\Lpa\DataModel\User\Address;

abstract class AbstractLpaActorController extends AbstractLpaController
{
    /**
     * @var Application\Model\FormFlowChecker
     */
    private $flowChecker;
        
    /**
     * Return clone source LPA details from session container, or from the api 
     * if not found in the session container. 
     * 
     * @param bool $trustOnly - when true, only return trust corporation details
     * 
     * @return Array|Null;
     */
    protected function getSeedDetails($trustOnly=false)
    {
        if($this->getLpa()->seed === null) return null;
        
        $seedId = $this->getLpa()->seed;
        $cloneContainer = new Container('clone');
        
        if(!$cloneContainer->offsetExists($seedId)) {
            
            // get seed data from the API
            $seedData = $this->getLpaApplicationService()->getSeedDetails($this->getLpa()->id);
            
            if(!$seedData) {
                return null;
            }
            
            // save seed data into session container
            $cloneContainer->$seedId = $seedData;
            
        }
        
        // get seed data from session container
        $seedData = $cloneContainer->$seedId;
        
        // get user's details
        $userSession = $this->getServiceLocator()->get('UserDetailsSession');
        
        // ordering and filtering the data 
        $seedDetails = [];
        
        if(!$trustOnly) {
            $seedDetails[] = [
            'label' => (string)$userSession->user->name . ' (myself)',
            'data'  => [
                'name-title'        => $userSession->user->name->title,
                'name-first'        => $userSession->user->name->first,
                'name-last'         => $userSession->user->name->last,
                'address-address1'  => ($userSession->user->address instanceof Address)?$userSession->user->address->address1:'',
                'address-address2'  => ($userSession->user->address instanceof Address)?$userSession->user->address->address2:'',
                'address-address3'  => ($userSession->user->address instanceof Address)?$userSession->user->address->address3:'',
                'address-postcode'  => ($userSession->user->address instanceof Address)?$userSession->user->address->postcode:'',
                'dob-date-day'      => ($userSession->user->dob instanceof Dob)?$userSession->user->dob->date->format('d'):'',
                'dob-date-month'    => ($userSession->user->dob instanceof Dob)?$userSession->user->dob->date->format('m'):'',
                'dob-date-year'     => ($userSession->user->dob instanceof Dob)?$userSession->user->dob->date->format('Y'):'',
                'email-address'     => $userSession->user->email->address,
                ],
            ];
        }
        
        foreach($seedData as $type => $actorData) {
            if($trustOnly) {
                switch($type) {
                    case 'primaryAttorneys':
                        foreach($actorData as $singleActorData) {
                            if($singleActorData['type'] == 'trust') {
                                $seedDetails[] = [
                                        'label' => $singleActorData['name'] . ' (was a Primary Attorney)',
                                        'data' => $this->seedDataFilter($singleActorData),
                                ];
                                
                                // only one trust can be in an LPA
                                return $seedDetails;
                            }
                        }
                        break;
                    case 'replacementAttorneys':
                        foreach($actorData as $singleActorData) {
                            if($singleActorData['type'] == 'trust') {
                                $seedDetails[] = [
                                        'label' => $singleActorData['name'] . ' (was a Replacement Attorney)',
                                        'data' => $this->seedDataFilter($singleActorData),
                                ];
                                
                                // only one trust can be in an LPA
                                return $seedDetails;
                            }
                        }
                        break;
                }
            }
            else {
                
                switch($type) {
                    case 'donor':
                        $seedDetails[] = [
                        'label' => $actorData['name']['first'].' '.$actorData['name']['last'] . ' (was a Donor)',
                        'data' => $this->seedDataFilter($actorData),
                        ];
                        break;
                    case 'correspondent':
                        if($actorData['who'] == 'other') {
                            $seedDetails[] = [
                            'label' => $actorData['name']['first'].' '.$actorData['name']['last'] . ' (was a Correspondent)',
                            'data' => $this->seedDataFilter($actorData),
                            ];
                        }
                        break;
                    case 'certificateProvider':
                        $seedDetails[] = [
                        'label' => $actorData['name']['first'].' '.$actorData['name']['last'] . ' (was a Certificate Provider)',
                        'data' => $this->seedDataFilter($actorData),
                        ];
                        break;
                    case 'primaryAttorneys':
                        foreach($actorData as $singleActorData) {
                            if($singleActorData['type'] == 'trust') continue;
                            $seedDetails[] = [
                                    'label' => $singleActorData['name']['first'].' '.$singleActorData['name']['last'] . ' (was a Primary Attorney)',
                                    'data' => $this->seedDataFilter($singleActorData),
                            ];
                        }
                        break;
                    case 'replacementAttorneys':
                        foreach($actorData as $singleActorData) {
                            if($singleActorData['type'] == 'trust') continue;
                            $seedDetails[] = [
                                    'label' => $singleActorData['name']['first'].' '.$singleActorData['name']['last'] . ' (was a Replacement Attorney)',
                                    'data' => $this->seedDataFilter($singleActorData),
                            ];
                        }
                        break;
                    case 'peopleToNotify':
                        foreach($actorData as $singleActorData) {
                            $seedDetails[] = [
                                    'label' => $singleActorData['name']['first'].' '.$singleActorData['name']['last'] . ' (was a person to be notified)',
                                    'data' => $this->seedDataFilter($singleActorData),
                            ];
                        }
                        break;
                    default: break;
                }
            }
        }
        
        return $seedDetails;
    }
    
    /**
     * Filtering seed details - only keep name, address, dob, email, etc.
     * 
     * @param array $seedData
     * @return array
     */
    private function seedDataFilter($seedData)
    {
        $filteredData = [];
        foreach($seedData as $name => $value) {
            switch($name) {
                case "name":
                case "otherNames":
                case "address":
                case "dob":
                case "email":
                case "phone":
                    $filteredData[$name] = $value;
                    break;
                default:
                    break;
            }
        }
        
        return $filteredData;
    }
    
    protected function seedDataSelector(ViewModel $viewModel, AbstractActorForm $mainForm, $trustOnly=false)
    {
        $seedDetails = $this->getSeedDetails($trustOnly);
        
        if($seedDetails == null) return;
        
        $seedDetailsPickerForm = $this->getServiceLocator()->get('FormElementManager')->get( 'Application\Form\Lpa\SeedDetailsPickerForm', ['seedDetails'=>$seedDetails] );
        $seedDetailsPickerForm->setAttribute( 'action', $this->url()->fromRoute( $this->getEvent()->getRouteMatch()->getMatchedRouteName(), ['lpa-id' => $this->getLpa()->id] ) );
        $viewModel->seedDetailsPickerForm = $seedDetailsPickerForm;
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
        
            if($postData->offsetExists('pick-details')) {
                
                // load seed data into the form or return form data in json format if request is an ajax
                $seedDetailsPickerForm->setData($this->request->getPost());
                
                if($seedDetailsPickerForm->isValid()) {
                    
                    $pickIdx = $this->request->getPost('pick-details');
                    
                    if(is_array($seedDetails) && array_key_exists($pickIdx, $seedDetails)) {
                        
                        $actorData = $seedDetails[$pickIdx]['data'];
                        
                        $formData = $this->flattenData($actorData);
                        
                        if ( $this->getRequest()->isXmlHttpRequest() ) {
                            return new JsonModel($formData);
                        }
                        else {
                            $mainForm->bind($formData);
                        }
                    }
                }
            }
        }
    }
}
