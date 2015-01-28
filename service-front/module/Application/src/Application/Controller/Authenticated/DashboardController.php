<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

class DashboardController extends AbstractAuthenticatedController
{
    public function indexAction()
    {

        $lpas = $this->getLpaList();

        echo '<h1>Dashboard!</h1>';

        echo "<p>ID: <strong>".$this->getUser()->id()."</strong></p>";
        echo "<p>Token: <strong>".$this->getUser()->token()."</strong></p>";
        echo "<p>Last login: <strong>".$this->getUser()->lastLogin()->format('r')."</strong></p>";

        echo "<br />\n";

        foreach( $lpas as $lpa ){
            if( $lpa->version == 2 ){
                echo "<p>id:{$lpa->id} - v{$lpa->version} - {$lpa->donor} - {$lpa->type} - {$lpa->updatedAt->format('r')}</p>\n";
            } else {
                echo "<p>id:{$lpa->id} - v{$lpa->version} - {$lpa->donor} - {$lpa->type} - {$lpa->updatedAt->format('r')}";
                echo ' <a href="/forward/lpa/'.$lpa->id.'">Load</a> ';
                echo "</p>\n";
            }
        }

        exit();

        return new ViewModel();
    }
    
    public function cloneAction()
    {
        
    }
    
    public function deleteLpaAction()
    {
        
    }

    //---

    private function getLpaList(){

        $lpas = array();

        /**
         * This should be the only point at which we touch the V1Proxy module!
         * When the time comes to deprecated v1, we should just be able to remove the below if statement.
         */
        if( $this->getServiceLocator()->has('ProxyDashboard') ){

            // This will return an empty array if the user has no v1 LPAs.
            $lpas = $this->getServiceLocator()->get('ProxyDashboard')->getLpas();

        }

        //----

        $v2Apis = $this->getServiceLocator()->get('ApiClient')->getApplicationList();

        foreach($v2Apis as $lpa){

            $obj = new \stdClass();

            $obj->id = $lpa->id;

            $obj->version = 2;

            $obj->donor = $lpa->document->donor->name->first . ' ' . $lpa->document->donor->name->last;

            $obj->type = $lpa->document->type;

            $obj->updatedAt = $lpa->updatedAt;

            $obj->status = 'Started';

            $lpas[] = $obj;
        }

        # Get the v2 LPAs.

        # Merge the list

        //---
        # Sort the list

        // Sort by updatedAt into decending order
        usort($lpas, function($a, $b){
            if ($a->updatedAt == $b->updatedAt) { return 0; }
            return ($a->updatedAt > $b->updatedAt) ? -1 : 1;
        });

        //---

        return $lpas;

    } // function

}
