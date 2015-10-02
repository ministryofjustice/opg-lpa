<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\ArrayAdapter as PaginatorArrayAdapter;

class DashboardController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        $query = $this->params()->fromQuery('search');

        if( is_string($query) && !empty($query) ){
            
            $paginator = $this->searchLpaList( $query );

        } else {

            // No search query - return all LPAs.

            $paginator = $this->getLpaList();

            // If the user currently has no LPAs, redirect them to create one...
            if( $paginator->getTotalItemCount() == 0 ){
                return $this->createAction();
            }

        }

        //---

        $paginator->setItemCountPerPage(200);

        $paginator->setCurrentPageNumber($this->params()->fromRoute('page'));

        //---

        // Determine if there are any v1 LPAs. Returns a bool.
        $hasV1Lpa = array_reduce( iterator_to_array($paginator), function( $carry, $lpa ){
            return $carry || ($lpa->version == 1);
        }, false );

        //---

        return new ViewModel([
            'hasV1Lpas' => $hasV1Lpa,
            'lpas' => $paginator,
            'freeText' => $query,
            'isSearch' => (is_string($query) && !empty($query)),
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
        
        return $this->redirect()->toRoute('user/dashboard');
    }

    //---

    /**
     * Displayed when the Terms and Conditions have changed since the user last logged in.
     */
    public function termsAction(){
        return new ViewModel();
    }

    //------------------------------------------------------------------

    /**
     * Returns a Paginator for all the user's LPAs.
     *
     * @return Paginator
     */
    private function getLpaList(){

        // Return all of the (v2) LPAs.
        $lpas = $this->getServiceLocator()->get('ApplicationList')->getAllALpaSummaries();

        //---

        /**
         * When removing v1, the whole if statement below can be deleted.
         *
         * #v1Code
         */
        if( $this->getServiceLocator()->has('ProxyDashboard') ){

            try {

                // This will return an empty array if the user has no v1 LPAs.
                $v1Lpas = $this->getServiceLocator()->get('ProxyDashboard')->getLpas();

                if( is_array($v1Lpas) ){

                    // Merge the v1 LPAs into the v2 list.
                    $lpas = array_merge($lpas, $v1Lpas);

                }

            } catch( \RuntimeException $e ){

                // Runtime errors are caused by a v1 / v2 auth token mismatch.
                // Re-authenticating is the only solution.
                // (realistically this only happens whilst we can login to both v1 & v2)
                $this->redirect()->toRoute('login', ['state'=>'timeout']);

            }

        } // if

        // end #v1Code

        //---

        // Sort by updatedAt into descending order
        // Once we remove #v1Code, perhaps we can assume they're pre-sorted from the API/DB?
        usort($lpas, function($a, $b){
            if ($a->updatedAt == $b->updatedAt) { return 0; }
            return ($a->updatedAt > $b->updatedAt) ? -1 : 1;
        });

        //---

        return new Paginator(new PaginatorArrayAdapter($lpas));

    } // function

    /**
     * Returns a Paginator for all the user's LPAs the match the given query.
     *
     * @return Paginator
     */
    private function searchLpaList( $query ){

        // Return all of the (v2) LPAs that match the query.
        $lpas = $this->getServiceLocator()->get('ApplicationList')->searchAllALpaSummaries( $query );

        //---

        /**
         * This should be the only point at which we touch the V1Proxy module!
         *
         * When removing v1, the whole if statement below can be deleted.
         *
         * #v1Code
         */
        if( $this->getServiceLocator()->has('ProxyDashboard') ){

            try {

                // This will return an empty array if the user has no v1 LPAs.
                $v1Lpas = $this->getServiceLocator()->get('ProxyDashboard')->searchLpas( $query );

                // Merge the v1 LPAs into the v2 list.
                $lpas = array_merge($lpas, $v1Lpas);

            } catch( \RuntimeException $e ){

                // Runtime errors are caused by a v1 / v2 auth token mismatch.
                // Re-authenticating is the only solution.
                // (realistically this only happens whilst we can login to both v1 & v2)
                return $this->redirect()->toRoute('login', ['state'=>'timeout']);

            }

        }

        // end #v1Code

        //---

        // Sort by updatedAt into descending order
        // Once we remove #v1Code, perhaps we can assume they're pre-sorted from the API/DB?
        usort($lpas, function($a, $b){
            if ($a->updatedAt == $b->updatedAt) { return 0; }
            return ($a->updatedAt > $b->updatedAt) ? -1 : 1;
        });

        //---

        return new Paginator(new PaginatorArrayAdapter($lpas));

    }

    //------------------------------------------------------------------

    /**
     * This is overridden to prevent people being (accidently?) directed to this controller post-auth.
     *
     * @return bool|\Zend\Http\Response
     */
    protected function checkAuthenticated( $allowRedirect = true ){

        return parent::checkAuthenticated( false );

    } // function

} // class
