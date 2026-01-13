<?php

namespace Application\Controller\Authenticated;

use Laminas\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;
use MakeShared\Logging\LoggerTrait;

/**
 * For deleting a user's account
 *
 * Class DeleteController
 * @package Application\Controller\Authenticated
 */
class DeleteController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    public function indexAction()
    {
        return new ViewModel();
    }

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
}
