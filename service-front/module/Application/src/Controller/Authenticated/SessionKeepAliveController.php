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
        if ($this->request->isPost()) {
            // Derive expireInSeconds from request POST
            $expireInSeconds = $this->request->getPost('expireInSeconds');

            if ($expireInSeconds === null) {
                $response = $this->getResponse();
                $response->setStatusCode(400);
                $response->setContent('Malformed request');
                return $response;
            }

            $remainingSeconds = $this->getAuthenticationService()->setSessionExpiry(intval($expireInSeconds));
            return new JsonModel(['remainingSeconds' => $remainingSeconds]);
        }

        $response = $this->getResponse();
        $response->setStatusCode(405);
        $response->setContent('Bad request method');
        return $response;
    }
}
