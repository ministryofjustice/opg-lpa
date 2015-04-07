<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

/**
 * For deleting a user's account
 *
 * Class DeleteController
 * @package Application\Controller\Authenticated
 */
class DeleteController extends AbstractAuthenticatedController {

    public function indexAction(){

        return new ViewModel();

    } // function

    public function confirmAction(){

        // Delete all v2 LPAs, their v2 Personal details, and their Auth account.
        $this->getServiceLocator()->get('DeleteUser')->delete();


        // If we are still using a v1 proxy...
        if( $this->getServiceLocator()->has('ProxyDashboard') ){

            // Delete all v1 LPAs and their Account Service account.
            $this->getServiceLocator()->get('ProxyDashboard')->deleteAllLpasAndAccount();
        }


        return $this->redirect()->toRoute( 'logout' );

    } // function

} // class
