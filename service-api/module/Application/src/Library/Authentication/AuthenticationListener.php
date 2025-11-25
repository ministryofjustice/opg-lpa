<?php

namespace Application\Library\Authentication;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Mvc\MvcEvent;
use MakeShared\Logging\LoggerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Authenticate the user from a header token.
 *
 * This is called pre-dispatch, triggered by MvcEvent::EVENT_ROUTE at priority 500.
 *
 * Class AuthenticationListener
 * @package Application\Library\Authentication
 */
class AuthenticationListener implements LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * @param MvcEvent $e
     * @return ApiProblemResponse|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @psalm-api
     */
    public function authenticate(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();

        $authService = $serviceManager->get('Laminas\Authentication\AuthenticationService');

        /*
         * Do some authentication. Initially this will just be via the token passed from front-2.
         * This token will have come from Auth-1. As this will be replaced we'll use a custom header value of:
         *      X-AuthOne
         *
         * This will leave the standard 'Authorization' namespace free for when OAuth is done properly.
         */
        // Suppress psalm errors caused by bug in laminas-mvc;
        // see https://github.com/laminas/laminas-mvc/issues/77
        /**
         * @psalm-suppress UndefinedInterfaceMethod
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

            // This calls LpaAuth->authenticate() behind the scenes, where we deal with the authentication
            // response, including exceptions thrown due to database issues
            $result = $authService->authenticate($authAdapter);

            $resultCode = $result->getCode();

            if (AuthenticationResult::SUCCESS === $resultCode) {
                $this->getLogger()->info('Authentication success');
            } elseif (AuthenticationResult::FAILURE_CREDENTIAL_INVALID === $resultCode) {
                $this->getLogger()->warning('Authentication failed', [
                    'status' => $resultCode,
                ]);
                return new ApiProblemResponse(new ApiProblem(401, 'Invalid authentication token'));
            } else {
                return new ApiProblemResponse(new ApiProblem(500, 'Uncategorised error'));
            }
        }
    }
}
