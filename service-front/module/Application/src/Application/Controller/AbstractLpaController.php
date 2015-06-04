<?php
namespace Application\Controller;

use RuntimeException;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Application\Model\FormFlowChecker;
use Application\Form\Lpa\AbstractForm;

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
        
        if(($view instanceof ViewModel) && ($view->form instanceof AbstractForm)) {
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
        
        foreach(array_merge($this->getLpa()->document->primaryAttorneys, $this->getLpa()->document->replacementAttorneys) as $attorney) {
            if($attorney instanceof TrustCorporation) {
                return true;
            }
        }
        return false;
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
                        $dob = new \DateTime($l3);
                        $formData['dob-date'] = [
                                'day'   => $dob->format('d'),
                                'month' => $dob->format('m'),
                                'year'  => $dob->format('Y'),
                        ];
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
