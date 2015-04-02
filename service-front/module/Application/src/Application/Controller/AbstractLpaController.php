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
}
