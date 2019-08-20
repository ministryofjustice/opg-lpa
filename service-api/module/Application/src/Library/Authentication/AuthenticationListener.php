<?php

namespace Application\Library\Authentication;

use Application\Model\Service\Authentication\Service as AuthenticationService;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Authentication\Result as AuthenticationResult;
use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

/**
 * Authenticate the user from a header token.
 *
 * This is called pre-dispatch, triggered by MvcEvent::EVENT_ROUTE at priority 500.
 *
 * Class AuthenticationListener
 * @package Application\Library\Authentication
 */
class AuthenticationListener
{
    use LoggerTrait;

    public function authenticate(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();

        $authService = $serviceManager->get('Zend\Authentication\AuthenticationService');

        /*
         * Do some authentication. Initially this will just be via the token passed from front-2.
         * This token will have come from Auth-1. As this will be replaced we'll use a custom header value of:
         *      X-AuthOne
         *
         * This will leave the standard 'Authorization' namespace free for when OAuth is done properly.
         */
        $token = $e->getRequest()->getHeader('Token');

        if (!$token) {
            //  No token; set Guest....
            $authService->getStorage()->write(new Identity\Guest());

            $this->getLogger()->info('No token, guest set in Authentication Listener');
        } else {
            $token = trim($token->getFieldValue());

            $this->getLogger()->info('Authentication attempt - token supplied');

            //  Attempt to authenticate - if successful the identity will be persisted for the request
            /** @var AuthenticationService $authenticationService */
            $authenticationService = $serviceManager->get(AuthenticationService::class);
            $config = $serviceManager->get('Config');

            $authAdapter = new Adapter\LpaAuth($authenticationService, $token, $config['admin']['accounts']);
            $result = $authService->authenticate($authAdapter);

            if (AuthenticationResult::SUCCESS !== $result->getCode()) {
                $this->getLogger()->info('Authentication failed');

                return new ApiProblemResponse(new ApiProblem(401, 'Invalid authentication token'));
            } else {
                $this->getLogger()->info('Authentication success');
            }
        }
    }
}
