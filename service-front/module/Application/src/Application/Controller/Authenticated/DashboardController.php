<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Zend\Session\Container;

class DashboardController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        $lpas = $this->getLpaList();

        //---

        // If the user currently has no LPAs, redirect them to create one...
        if( empty($lpas) ){
            return $this->createAction();
        }

        //---

        return new ViewModel([
            'lpas' => $lpas,
            'version' => [
                'commit' => $this->config()['version']['commit'],
                'cache' => $this->config()['version']['cache'],
            ],
            'user' => [
                'id' => $this->getUser()->id(),
                'token' => $this->getUser()->token(),
                'lastLogin' => $this->getUser()->lastLogin()->format('r'),
            ],
            'pageTitle' => 'Your LPAs',
        ]);
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

            $this->flashMessenger()->addErrorMessage('Error creating a new LPA. Please try again.');
            return $this->redirect()->toRoute( 'user/dashboard' );

        }

        //-------------------------------------
        // If we're seeding the new LPA...

        if( ($seedId = $this->params()->fromRoute('lpa-id')) != null ){

            $result = $this->getLpaApplicationService()->setSeed( $newLpaId, (int)$seedId );
            
            $this->resetSessionCloneData($seedId);

            if( $result !== true ){
                $this->flashMessenger()->addWarningMessage('LPA created but could not set seed');
            }

        }

        //---

        // Redirect them to the first page...
        return $this->redirect()->toRoute( 'lpa/form-type', [ 'lpa-id'=>$newLpaId ] );

    } // function
    
    public function deleteLpaAction()
    {
        $lpaId = $this->getEvent()->getRouteMatch()->getParam('lpa-id');
        if(!$this->getLpaApplicationService()->deleteApplication($lpaId)) {
            throw new \RuntimeException('API client failed to delete LPA for id: '.$lpaId);
        }
        
        $this->redirect()->toRoute('user/dashboard');
    }

    //---

    /**
     * Displayed when the Terms and Conditions have changed since the user last logged in.
     */
    public function termsAction(){
        return new ViewModel();
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

        # TODO - Move this to the service level.

        $v2Apis = $this->getServiceLocator()->get('LpaApplicationService')->getApplicationList();

        foreach($v2Apis as $lpa){

            $obj = new \stdClass();

            $obj->id = $lpa->id;

            $obj->version = 2;

            $obj->donor = ((($lpa->document->donor instanceof Donor) && ($lpa->document->donor->name instanceof Name))?$lpa->document->donor->name->__toString():'');

            $obj->type = $lpa->document->type;

            $obj->updatedAt = $lpa->updatedAt;

            $obj->progress = ($lpa->completedAt instanceof \DateTime)?'Completed':(($lpa->createdAt instanceof \DateTime)?'Created':'Started');

            $lpas[] = $obj;
        }

        //---
        # Sort the list

        // Sort by updatedAt into descending order
        usort($lpas, function($a, $b){
            if ($a->updatedAt == $b->updatedAt) { return 0; }
            return ($a->updatedAt > $b->updatedAt) ? -1 : 1;
        });

        //---

        return $lpas;

    } // function

}
