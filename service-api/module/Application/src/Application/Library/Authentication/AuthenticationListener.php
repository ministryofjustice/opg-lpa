<?php
namespace Application\Library\Authentication;

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
class AuthenticationListener {

    public function authenticate( MvcEvent $e ){

        $auth = $e->getApplication()->getServiceManager()->get('AuthenticationService');

        /*
         * Do some authentication. Initially this will will just be via the token passed from front-2.
         * This token will have come from Auth-1. As this will be replaced we'll use a custom header value of:
         *      X-AuthOne
         *
         * This will leave the standard 'Authorization' namespace free for when OAuth is done properly.
         */
        $token = $e->getRequest()->getHeader('X-AuthOne');

        if (!$token) {

            // No token; set Guest....
            $auth->getStorage()->write( new Identity\Guest() );

        } else {

            $token = trim($token->getFieldValue());

            $authAdapter = new Adapter\LpaAuthOne( $token );

            // If successful, the identity will be persisted for the request.
            $result = $auth->authenticate($authAdapter);

            if( AuthenticationResult::SUCCESS !== $result->getCode() ){
                return new ApiProblemResponse( new ApiProblem( 401, 'Invalid authentication token' ) );
            }

        }

    } // function

} // class
