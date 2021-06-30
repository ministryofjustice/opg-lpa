<?php

namespace Application\Controller\Authenticated;

use Laminas\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

/**
 * For deleting a user's account
 *
 * Class DeleteController
 * @package Application\Controller\Authenticated
 */
class DeleteController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        return new ViewModel();
    }

    /**
     * @return ViewModel|\Laminas\Http\Response
     */
    public function confirmAction()
    {
        //  Delete all v2 LPAs, their v2 Personal details, and their Auth account.
        if (!$this->getUserService()->delete()) {
            $view = new ViewModel();
            $view->setTemplate('error/500.twig');

            return $view;
        }

        return $this->redirect()->toRoute('deleted');
    }

    /**
     * This is overridden to prevent people being (accidently?) directed to this controller post-auth.
     *
     * @return bool|\Laminas\Http\Response
     */
    protected function checkAuthenticated($allowRedirect = true)
    {
        return parent::checkAuthenticated(false);
    }
}
