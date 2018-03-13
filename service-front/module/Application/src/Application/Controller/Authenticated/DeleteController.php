<?php

namespace Application\Controller\Authenticated;

use Application\Model\Service\User\Delete;
use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

/**
 * For deleting a user's account
 *
 * Class DeleteController
 * @package Application\Controller\Authenticated
 */
class DeleteController extends AbstractAuthenticatedController
{
    /**
     * @var Delete
     */
    private $deleteUser;

    public function indexAction(){

        return new ViewModel();

    } // function

    public function confirmAction(){

        // Delete all v2 LPAs, their v2 Personal details, and their Auth account.
        if ($this->deleteUser->delete()) {
            // Redirect them to the deleted page.
            return $this->redirect()->toRoute( 'deleted' );
        } else {
            $view = new ViewModel();
            $view->setTemplate('error/500.twig');
            return $view;
        }

    } // function


    /**
     * This is overridden to prevent people being (accidently?) directed to this controller post-auth.
     *
     * @return bool|\Zend\Http\Response
     */
    protected function checkAuthenticated( $allowRedirect = true ){

        return parent::checkAuthenticated( false );

    } // function

    public function setDeleteUser(Delete $deleteUser)
    {
        $this->deleteUser = $deleteUser;
    }

} // class
