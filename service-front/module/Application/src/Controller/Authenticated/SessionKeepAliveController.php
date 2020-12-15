<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Laminas\View\Model\JsonModel;

class SessionKeepAliveController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        return new JsonModel(['refreshed' => $this->getSessionManager()->sessionExists()]);
    }

}
