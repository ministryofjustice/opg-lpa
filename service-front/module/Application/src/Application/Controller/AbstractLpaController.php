<?php

namespace Application\Controller;

use Application\Controller\AbstractAuthenticatedController;
use Zend\Mvc\MvcEvent;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;

abstract class AbstractLpaController extends AbstractAuthenticatedController implements LpaAwareInterface
{
    private $lpa;
    
    public function onDispatch(MvcEvent $e)
    {
        parent::onDispatch($e);
        
        /**
         * check the requested route and redirect user to the correct one if the requested route is not available.
         */   
        $checker = new FormFlowChecker($this->getLpa());
        $checker->setLpa($this->getLpa());
        $currentRoute = $e->getRouteMatch()->getMatchedRouteName();
        $personIndex = $e->getRouteMatch()->getParam('person_index');
        
        $calculatedRoute = $checker->check($currentRoute, $personIndex);
        if($calculatedRoute && ($calculatedRoute != $currentRoute)) {
            $this->redirect()->toRoute($calculatedRoute);
        }
    }
    
    /**
     * @return the $lpa
     */
    public function getLpa ()
    {
        if(!($this->lpa instanceof Lpa)) $this->lpa = new Lpa();
        return $this->lpa;
    }
    
    /**
     * @param field_type $lpa
     */
    public function setLpa ($lpa)
    {
        $this->lpa = $lpa;
    }
    
}
