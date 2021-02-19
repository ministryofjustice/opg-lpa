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

    public function setExpiryAction()
    {
        // TODO derive from request POST
        $expireInSeconds = 302;

        $remainingSeconds = $this->getAuthenticationService()->setSessionExpiry($expireInSeconds);

        return new JsonModel(['remainingSeconds' => $remainingSeconds]);
    }
}
