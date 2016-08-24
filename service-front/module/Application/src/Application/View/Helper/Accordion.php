<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Accordion extends AbstractHelper {

    private $lpa;

    private $bars = [
        'lpa/form-type'                              ,
        'lpa/donor'                                  ,
        'lpa/when-lpa-starts'                        ,
        'lpa/life-sustaining'                        ,
        'lpa/primary-attorney'                       ,
        'lpa/how-primary-attorneys-make-decision'    ,
        'lpa/replacement-attorney'                   ,
        'lpa/when-replacement-attorney-step-in'      ,
        'lpa/how-replacement-attorneys-make-decision',
        'lpa/certificate-provider'                   ,
        'lpa/people-to-notify'                       ,
        'lpa/instructions'                           ,
        'lpa/summary'                                ,
        'lpa/applicant'                              ,
        'lpa/correspondent'                          ,
        'lpa/who-are-you'                            ,
        'lpa/repeat-application'                     ,
        'lpa/fee-reduction'                          ,
    ];

    public function __invoke( Lpa $lpa = null ){

        $this->lpa = $lpa;
        return $this;

    }

    public function top(){

        if( is_null($this->lpa) ){ return array(); }

        //---

        $flowChecker = new FormFlowChecker( $this->lpa );

        $barsInPlay = array();

        $currentRoute = $this->getRouteName();
        $includeUpToRoute = $flowChecker->backToForm();

        foreach( $this->bars as $route ){

            // Break at the route we are up to...
            if( $includeUpToRoute == $route ){
                break;
            }

            // Break if we get to the current route...
            if( $currentRoute == $route ){
                break;
            }

            // True iff the user is allowed to view this route name...
            if( $route == $flowChecker->getNearestAccessibleRoute($route) ) {

                $barsInPlay[] = [ 'routeName' =>  $route ];

            }

        } // foreach

        return $barsInPlay;

    }

    public function bottom(){

        if( is_null($this->lpa) ){ return array(); }

        //---

        $flowChecker = new FormFlowChecker( $this->lpa );

        $barsInPlay = array();

        $currentRoute = $this->getRouteName();
        $includeUpToRoute = $flowChecker->backToForm();

        //---

        // Skip all routes before the current route...
        $startAt = array_search( $currentRoute, $this->bars );
        $startAt = is_int($startAt) ? $startAt : 0;
        $bars = array_slice($this->bars, $startAt );

        foreach( $bars as $route ){

            // Break at the route we are up to...
            if( $includeUpToRoute == $route ){
                break;
            }

            // Skip the current route...
            if( $currentRoute == $route ){
                continue;
            }

            // True iff the user is allowed to view this route name...
            if( $route == $flowChecker->getNearestAccessibleRoute($route) ) {

                $barsInPlay[] = [ 'routeName' =>  $route ];

            }

        } // foreach

        return $barsInPlay;

    }

    //-----------------------------

    protected function getRouteName(){
        $serviceManager = $this->getView()->getHelperPluginManager()->getServiceLocator();
        $application = $serviceManager->get('application');
        return $application->getMvcEvent()->getRouteMatch()->getMatchedRouteName();
    }

} // class
