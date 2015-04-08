<?php
namespace Application\Controller;

use RuntimeException;

use Zend\Mvc\MvcEvent;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Zend\Session\Container;

abstract class AbstractLpaController extends AbstractAuthenticatedController implements LpaAwareInterface
{
    /**
     * @var LPA The LPA currently referenced in to the URL
     */
    private $lpa;
    
    /**
     * @var Application\Model\FormFlowChecker
     */
    private $flowChecker;
    
    public function onDispatch(MvcEvent $e)
    {

        //----------------------------------------------------------------------
        // Check we have a user set, thus ensuring an authenticated user

        if( ($authenticated = $this->checkAuthenticated()) !== true ){
            return $authenticated;
        }

        //---

        # load content header in the layout if controller has a $contentHeader
        if(isset($this->contentHeader)) {
            $this->layout()->contentHeader = $this->contentHeader;
        }
        
        # inject lpa into layout.
        $this->layout()->lpa = $this->getLpa();
        
        /**
         * check the requested route and redirect user to the correct one if the requested route is not available.
         */   
        $currentRoute = $e->getRouteMatch()->getMatchedRouteName();
        
        // get extra input query param from the request url.
        if($currentRoute == 'lpa/download') {
            $param = $e->getRouteMatch()->getParam('pdf-type');
        }
        else {
            $param = $e->getRouteMatch()->getParam('idx');
        }
        
        // call flow checker to get the nearest accessible route.
        $calculatedRoute = $this->getFlowChecker()->getNearestAccessibleRoute($currentRoute, $param);
        
        // if false, do not run action method.
        if($calculatedRoute === false) {
            return $this->response;
        }
        
        // redirect to the calculated route if it is not equal to the current route
        if($calculatedRoute != $currentRoute) {
            return $this->redirect()->toRoute($calculatedRoute, ['lpa-id'=>$this->getLpa()->id]);
        }
        
        // inject lpa into view
        $view = parent::onDispatch($e);
        if($view instanceof ViewModel) {
            $view->setVariable('lpa', $this->getLpa());
        }
        
        return $view;
    }
    
    /**
     * Returns the LPA currently referenced in to the URL
     *
     * @return Lpa
     */
    public function getLpa ()
    {
        if( !( $this->lpa instanceof Lpa ) ){
            throw new RuntimeException('A LPA has not been set');
        }
        return $this->lpa;
    }
    
    /**
     * Sets the LPA currently referenced in to the URL
     *
     * @param Lpa $lpa
     */
    public function setLpa ( Lpa $lpa )
    {
        $this->lpa = $lpa;
    }
    
    /**
     * @return \Application\Controller\Application\Model\FormFlowChecker
     */
    public function getFlowChecker()
    {
        if($this->flowChecker == null) {
            $formFlowChecker = new FormFlowChecker($this->getLpa());
            $formFlowChecker->setLpa($this->getLpa());
            $this->flowChecker = $formFlowChecker;
        }
        
        return $this->flowChecker;
    }

    /**
     * Check if LPA has a trust corporation attorney in either primary or replacement attorneys
     * 
     * @return boolean
     */
    protected function hasTrust()
    {
        $hasTrust = false;
        
        $primaryAttorneys = $this->getLpa()->document->primaryAttorneys;
        if(!is_array($primaryAttorneys)) {
            $primaryAttorneys = [];
        }
        
        $replacementAttorneys = $this->getLpa()->document->replacementAttorneys;
        if(!is_array($replacementAttorneys)) {
            $replacementAttorneys = [];
        }
        
        foreach(array_merge($primaryAttorneys, $replacementAttorneys) as $attorney) {
            if($attorney instanceof TrustCorporation) {
                return true;
            }
        }
        return false;
    }
    
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
        if(!($this->lpa instanceof Lpa) || ($this->lpa->seed === null)) return null;
        
        $seedId = $this->lpa->seed;
        $cloneContainer = new Container('clone');
        unset($cloneContainer->$seedId);
        if(!$cloneContainer->offsetExists($seedId)) {
            
            // get seed data from the API
            $seedData = $this->getLpaApplicationService()->getSeedDetails($this->lpa->id);
            if(!$seedData) {
                return null;
            }
            
            // save seed data into session container
            $cloneContainer->$seedId = $seedData;
            
        }
        
        $seedData = $cloneContainer->$seedId;
        
        // ordering the data
        $seedDetails = [];
        foreach($seedData as $type => $actorData) {
            if($trustOnly) {
                switch($type) {
                    case 'primaryAttorneys':
                        foreach($actorData as $singleActorData) {
                            if($singleActorData['type'] == 'trust') {
                                $seedDetails[] = [
                                        'label' => $singleActorData['name'] . ' (was a Primary Attorney)',
                                        'data' => $singleActorData,
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
                                        'data' => $singleActorData,
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
                        'data' => $actorData,
                        ];
                        break;
                    case 'correspondent':
                        if($actorData['who'] == 'other') {
                            $seedDetails[] = [
                            'label' => $actorData['name']['first'].' '.$actorData['name']['last'] . ' (was a Correspondent)',
                            'data' => $actorData,
                            ];
                        }
                        break;
                    case 'certificateProvider':
                        $seedDetails[] = [
                        'label' => $actorData['name']['first'].' '.$actorData['name']['last'] . ' (was a Certificate Provider)',
                        'data' => $actorData,
                        ];
                        break;
                    case 'primaryAttorneys':
                        foreach($actorData as $singleActorData) {
                            if($singleActorData['type'] == 'trust') continue;
                            $seedDetails[] = [
                                    'label' => $singleActorData['name']['first'].' '.$singleActorData['name']['last'] . ' (was a Primary Attorney)',
                                    'data' => $singleActorData,
                            ];
                        }
                        break;
                    case 'replacementAttorneys':
                        foreach($actorData as $singleActorData) {
                            if($singleActorData['type'] == 'trust') continue;
                            $seedDetails[] = [
                                    'label' => $singleActorData['name']['first'].' '.$singleActorData['name']['last'] . ' (was a Replacement Attorney)',
                                    'data' => $singleActorData,
                            ];
                        }
                        break;
                    case 'peopleToNotify':
                        foreach($actorData as $singleActorData) {
                            $seedDetails[] = [
                                    'label' => $singleActorData['name']['first'].' '.$singleActorData['name']['last'] . ' (was a person to be notified)',
                                    'data' => $singleActorData,
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
     * Convert model/seed data for populating into form
     * 
     * @param array $modelData - eg. [name=>[title=>'Mr', first=>'John', last=>'Smith']]
     * @return array - eg [name-title=>'Mr', name-first=>'John', name-last=>'Smith']
     */
    protected function flattenData($modelData)
    {
        $formData = [];
        foreach($modelData as $l1 => $l2) {
            if(is_array($l2)) {
                foreach($l2 as $name=>$l3) {
                    if($l1=='dob') {
                        $formData['dob-date-day'] = (new \DateTime($l3))->format('d');
                        $formData['dob-date-month'] = (new \DateTime($l3))->format('m');
                        $formData['dob-date-year'] = (new \DateTime($l3))->format('Y');
                    }
                    else {
                        $formData[$l1.'-'.$name] = $l3;
                    }
                }
            }
            else {
                $formData[$l1] = $l2;
            }
        }
        
        return $formData;
    }
}
