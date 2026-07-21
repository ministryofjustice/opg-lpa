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
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function authenticate(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();
        $authService = $serviceManager->get('Laminas\Authentication\AuthenticationService');

        // Check for admin service credential first. The admin app authenticates
        // via a pre-shared secret rather than a user token. Network-level security
        // (VPC security groups) is the primary control; this provides an explicit identity.
        /** @psalm-suppress UndefinedInterfaceMethod */
        $adminAuthHeader = $e->getRequest()->getHeader('X-Shared-Secret');

        if ($adminAuthHeader) {
            $config = $serviceManager->get('Config');
            $adminServiceSecret = $config['admin']['service_secret'] ?? '';

            if ($adminServiceSecret !== '' && trim($adminAuthHeader->getFieldValue()) === $adminServiceSecret) {
                $authService->getStorage()->write(new Identity\AdminService());
                $this->getLogger()->info('Admin service authenticated via service secret');
                return;
            }
        }

        /** @psalm-suppress UndefinedInterfaceMethod */
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

            $authAdapter = new Adapter\LpaAuth($authenticationService, $token);

            // This calls LpaAuth->authenticate() behind the scenes, where we deal with the authentication
            // response, including exceptions thrown due to database issues
            $result = $authService->authenticate($authAdapter);

            $resultCode = $result->getCode();

            if (AuthenticationResult::SUCCESS === $resultCode) {
                $this->getLogger()->info('Authentication success');
            } elseif (AuthenticationResult::FAILURE_CREDENTIAL_INVALID === $resultCode) {
                $this->getLogger()->warning('Authentication failed', [
                    'status' => 'INVALID_CREDENTIALS',
                ]);
                return new ApiProblemResponse(new ApiProblem(401, 'Invalid authentication token'));
            } else {
                return new ApiProblemResponse(new ApiProblem(500, 'Uncategorised error'));
            }
        }
    }
}
