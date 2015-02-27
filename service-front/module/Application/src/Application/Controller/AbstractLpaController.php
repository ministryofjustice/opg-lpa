<?php
namespace Application\Controller;

use RuntimeException;

use Zend\Mvc\MvcEvent;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;

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
        $personIndex = $e->getRouteMatch()->getParam('idx');
        
        $calculatedRoute = $this->getFlowChecker()->check($currentRoute, $personIndex);
        
        if($calculatedRoute && ($calculatedRoute != $currentRoute)) {
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

    
    protected function hasTrust()
    {
        $hasTrust = false;
        foreach(array_merge($this->getLpa()->document->primaryAttorneys,$this->getLpa()->document->replacementAttorneys) as $attorney) {
            if($attorney instanceof TrustCorporation) {
                return true;
            }
        }
        return false;
    }
}
