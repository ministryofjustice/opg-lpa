<?php

namespace Application\Controller\Authenticated;

use Zend\Session\Container as SessionContainer;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

class DashboardController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        return new ViewModel([
            'lpas' => $this->getLpaList(),
            'version' => [
                'commit' => $this->config()['version']['commit'],
                'cache' => $this->config()['version']['cache'],
            ],
            'user' => [
                'id' => $this->getUser()->id(),
                'token' => $this->getUser()->token(),
                'lastLogin' => $this->getUser()->lastLogin()->format('r'),
            ]
        ]);
        
        $lpas = $this->getLpaList();

        echo '<h1>Dashboard!</h1>';

        echo '<h3><a href="/user/dashboard/create">Create new LPA</a></h3>';

        echo '<h4>Commit: '.$this->config()['version']['commit'].'</h4>';
        echo '<h4>Cache: '.$this->config()['version']['cache'].'</h4>';

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

    }

    /**
     * Creates a new LPA
     *
     * If 'lpa-id' is set, use the passed ID to seed the new LPA.
     */
    public function createAction(){

        //-------------------------------------
        // Create a new LPA...

        $newLpaId = $this->getLpaApplicationService()->createApplication();

        if( $newLpaId === false ){
            // Bad things happened!
            die('Error!');
        }

        //-------------------------------------
        // If we're seeding the new LPA...

        if( ($seedId = $this->params()->fromRoute('lpa-id')) != null ){

            $result = $this->getLpaApplicationService()->setSeed( $newLpaId, (int)$seedId );

            if( $result !== true ){
                $messages = new SessionContainer('FlashMessages');
                $messages->warning = 'LPA created but could not set seed';
            }

        }

        //---

        // Redirect them to the first page...
        return $this->redirect()->toRoute( 'lpa/form-type', [ 'lpa-id'=>$newLpaId ] );

    } // function
    
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

        $v2Apis = $this->getServiceLocator()->get('LpaApplicationService')->getApplicationList();

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
