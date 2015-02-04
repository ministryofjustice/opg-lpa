<?php
namespace Application\Controller;

use RuntimeException;

use Zend\Mvc\MvcEvent;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;

abstract class AbstractLpaController extends AbstractAuthenticatedController implements LpaAwareInterface
{
    private $lpa;
    
    public function onDispatch(MvcEvent $e)
    {
        # load content header in the layout if controller has a $contentHeader
        if(isset($this->contentHeader)) {
            $this->layout()->contentHeader = $this->contentHeader;
        }
        
        # inject lpa into layout.
        $this->layout()->lpa = $this->getLpa();
        
        # @todo: remove the line below once form data can persist.
        return parent::onDispatch($e);
        
        /**
         * check the requested route and redirect user to the correct one if the requested route is not available.
         */   
        $checker = new FormFlowChecker($this->getLpa());
        $checker->setLpa($this->getLpa());
        $currentRoute = $e->getRouteMatch()->getMatchedRouteName();
        $personIndex = $e->getRouteMatch()->getParam('person_index');
        
        $calculatedRoute = $checker->check($currentRoute, $personIndex);
        
        if($calculatedRoute && ($calculatedRoute != $currentRoute)) {
            return $this->redirect()->toRoute($calculatedRoute);
        }
        
        return parent::onDispatch($e);
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
    
}
