<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\View\Model\JsonModel;

class SessionKeepAliveController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        return new JsonModel(['refreshed' => $this->getSessionManager()->sessionExists()]);
    }

    public function setExpiryAction()
    {
        /** @var HttpRequest */
        $request = $this->request;

        if ($request->isPost()) {
            // Derive expireInSeconds from request body
            $expireInSeconds = null;

            $content = $request->getContent();
            if ($content !== '') {
                $decodedContent = json_decode($content, true);
                if (array_key_exists('expireInSeconds', $decodedContent)) {
                    $expireInSeconds = $decodedContent['expireInSeconds'];
                }
            }

            if ($expireInSeconds === null) {
                /** @var HttpResponse */
                $response = $this->getResponse();

                $response->setStatusCode(400);
                $response->setContent('Malformed request');
                return $response;
            }

            $remainingSeconds = $this->getAuthenticationService()->setSessionExpiry(intval($expireInSeconds));
            return new JsonModel(['remainingSeconds' => $remainingSeconds]);
        }

        /** @var HttpResponse */
        $response = $this->getResponse();

        $response->setStatusCode(405);
        $response->setContent('Method not allowed');
        return $response;
    }
}
