<?php

namespace Application\Library\Authentication;

use Auth\Model\Service\AuthenticationService;
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

        $config = $serviceManager->get('Config');
        $authConfig = $config['authentication'];

        /*
         * Do some authentication. Initially this will will just be via the token passed from front-2.
         * This token will have come from Auth-1. As this will be replaced we'll use a custom header value of:
         *      X-AuthOne
         *
         * This will leave the standard 'Authorization' namespace free for when OAuth is done properly.
         */
        $token = $e->getRequest()->getHeader('Token');

        if (!$token) {
            //  Check to see if this is a request from the auth service to clean up data
            $token = $e->getRequest()->getHeader('AuthCleanUpToken');

            if ($token && trim($token->getFieldValue()) == $authConfig['clean-up-token']) {
                //  Set identity as the auth service
                $authService->getStorage()->write(new Identity\AuthService());

                $this->getLogger()->info('Authentication success - auth service for clean up');
            } else {
                //  No token; set Guest....
                $authService->getStorage()->write(new Identity\Guest());

                $this->getLogger()->info('No token, guest set in Authentication Listener');
            }
        } else {
            $token = trim($token->getFieldValue());

            $this->getLogger()->info('Authentication attempt - token supplied');

            //  Attempt to authenticate - if successful the identity will be persisted for the request
            /** @var AuthenticationService $authenticationService */
            $authenticationService = $serviceManager->get(AuthenticationService::class);

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
