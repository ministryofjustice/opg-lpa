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

        $service = $this->getServiceLocator()->get('DeleteUser');

        $service->delete();

        return $this->redirect()->toRoute( 'logout' );

    } // function

} // class
