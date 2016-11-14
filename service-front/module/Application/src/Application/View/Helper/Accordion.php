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
        'lpa/applicant'                              ,
        'lpa/correspondent'                          ,
        'lpa/who-are-you'                            ,
        'lpa/repeat-application'                     ,
        'lpa/fee-reduction'                          ,
    ];

    private $excludedRoutes = [
        'lpa/summary',
        'lpa/complete',
        'lpa/date-check',
        'lpa/view-docs',
        'lpa/checkout',
        'lpa/checkout/pay/response',
        'lpa/checkout/worldpay',
        'lpa/checkout/worldpay/return/cancel',
        'lpa/checkout/worldpay/return/success',
        'lpa/checkout/worldpay/return/failure',
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

        //---

        if ( in_array( $currentRoute, $this->excludedRoutes ) ) {
            // No accordion summary when viewing table summary
            return [];
        }

        //---

        // If the route for us to include up to is earlier than the current route...
        if( array_search( $includeUpToRoute, $this->bars ) < array_search( $currentRoute, $this->bars ) ){
            $includeUpToRoute = $currentRoute;
        }

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

        //---

        // Added the special case bar for the review link.
        if( array_search( $currentRoute, $this->bars ) >= array_search( 'lpa/applicant', $this->bars ) ){
            $barsInPlay[] = [ 'routeName' =>  'review-link' ];
        }

        //---

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

        if ( in_array( $currentRoute, $this->excludedRoutes ) ) {
            // No accordion summary when viewing table summary
            return [];
        }

        //---

        // Skip all routes before the current route...
        $startAt = array_search( $currentRoute, $this->bars );
        $startAt = is_int($startAt) ? $startAt : 0;
        $bars = array_slice($this->bars, $startAt+1 ); // +1 to start one past the page the current page.

        // For each possible page, starting from the user's current location...
        foreach( $bars as $key=>$route ){

            //echo "Current route to check: $route<br />";

            // We only want to include 'this' bar if we can access 'this' page; AND
            // only if we can also access ANY other page after it.

            // Check we can access 'this' page...
            if( $route == $flowChecker->getNearestAccessibleRoute($route) ) {

                //echo "Can access: $route<br />";

                // Then check there are more pages...
                if( isset($bars[ $key+1 ]) ){

                    // And that we can access at least one of them...
                    foreach( array_slice($bars, $key+1 ) as $futureRoute ){

                        //$found = ($futureRoute == $flowChecker->getNearestAccessibleRoute($futureRoute));
                        //echo "From $route, can we access$futureRoute = ".(int)$found."<br />";

                        // If we are able to access a future route, then this page is complete.
                        if ( $futureRoute == $flowChecker->getNearestAccessibleRoute($futureRoute) ) {

                            // All conditions met, so add the bar.
                            $barsInPlay[] = ['routeName' => $route];

                            // We only need one page, so break when the first is found.
                            break;

                        } // if

                    } // foreach

                } elseif( $route == 'lpa/fee-reduction' ) {

                    // The last page is a special case as we cannot check past it.

                    // Therefore we have a custom check.
                    if($this->lpa->payment instanceof \Opg\Lpa\DataModel\Lpa\Payment\Payment) {
                        $barsInPlay[] = ['routeName' => $route];
                    }

                } // if

            } // if

            // Give up here as we'd never show a bar past where the user has been.
            if( $includeUpToRoute == $route ){ break; }

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
