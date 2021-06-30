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

    /**
     * @return JsonModel|\Laminas\Stdlib\ResponseInterface
     */
    public function setExpiryAction()
    {
        if ($this->request->isPost()) {
            // Derive expireInSeconds from request body
            $expireInSeconds = null;

            $content = $this->request->getContent();
            if ($content !== '') {
                $decodedContent = json_decode($content, true);
                if (array_key_exists('expireInSeconds', $decodedContent)) {
                    $expireInSeconds = $decodedContent['expireInSeconds'];
                }
            }

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
        $response->setContent('Method not allowed');
        return $response;
    }
}
