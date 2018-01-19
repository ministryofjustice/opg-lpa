<?php
namespace Application\Library\Authentication;

use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\MvcEvent;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

use Zend\Authentication\Result as AuthenticationResult;

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
        $auth = $e->getApplication()->getServiceManager()->get('AuthenticationService');

        $config = $e->getApplication()->getServiceManager()->get('Config');

        /*
         * Do some authentication. Initially this will will just be via the token passed from front-2.
         * This token will have come from Auth-1. As this will be replaced we'll use a custom header value of:
         *      X-AuthOne
         *
         * This will leave the standard 'Authorization' namespace free for when OAuth is done properly.
         */
        $token = $e->getRequest()->getHeader('Token');

        if (!$token) {
            // No token; set Guest....
            $auth->getStorage()->write(new Identity\Guest());

            $this->getLogger()->info('No token, guest set in Authentication Listener');
        } else {
            $token = trim($token->getFieldValue());

            $this->getLogger()->info('Authentication attempt - token supplied');

            $authAdapter = new Adapter\LpaAuth($token, $config['authentication']['endpoint'], $config['admin']);

            // If successful, the identity will be persisted for the request.
            $result = $auth->authenticate($authAdapter);

            if (AuthenticationResult::SUCCESS !== $result->getCode()) {
                $this->getLogger()->info('Authentication failed');

                return new ApiProblemResponse(new ApiProblem(401, 'Invalid authentication token'));
            } else {
                $this->getLogger()->info('Authentication success');

                // On SUCCESS, we don't return anything (as we're in a Listener).
            }
        }
    }
}
