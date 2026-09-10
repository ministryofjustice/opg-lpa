<?php

namespace Application\Controller\Version2\Auth;

use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\Users\Service as UserService;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Psr\Log\LoggerInterface;
use Random\RandomException;

class UsersController extends AbstractRestfulController
{
    public function __construct(
        public readonly SharedSpaceService $sharedSpaceService,
        public readonly UserService $userService,
        public readonly AuthenticationService $authenticationService,
        public readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws RandomException
     */
    public function create($data): ApiProblem|Json
    {
        if (isset($data['activationToken'])) {
            return $this->activateAccount(trim($data['activationToken']));
        } elseif (isset($data['username']) && isset($data['password'])) {
            return $this->createAccount(trim($data['username']), $data['password']);
        }

        return new ApiProblem(400, 'Either activationToken or username & password must be passed');
    }

    /**
     * @throws RandomException
     */
    private function createAccount(string $username, $password): ApiProblem|Json
    {
        $result = $this->userService->create($username, $password);

        if (is_string($result)) {
            return new ApiProblem(400, $result);
        }

        $this->logger->info('New user account created', $result);

        return new Json($result);
    }

    private function activateAccount(string $activationToken): ApiProblem|Json
    {
        $result = $this->userService->activate($activationToken);

        if (is_string($result)) {
            return new ApiProblem(400, $result);
        }

        $this->logger->info('New user account activated', [
            'activation_token' => $activationToken
        ]);

        // Return 204 - No Content
        // Note: The Laminas AbstractRestfulController response member
        // variable is an instance of Laminas\Stdlib\ResponseInterface, which doesn't
        // have a setStatusCode() method. However, in the getResponse() method of
        // AbstractRestfulController, we see this member variable being populated
        // with a Laminas\Http\Response instance, which does.
        // psalm doesn't like this, which is why we mark this as "ignored", until
        // Laminas fixes the bug - see https://github.com/laminas/laminas-mvc/issues/77
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $this->response->setStatusCode(204);

        return new Json([]);
    }

    /**
     * @param mixed $id
     * @return NoContentResponse|ApiProblem
     */
    public function delete($id)
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        $token = $this->getRequest()->getHeader('Token');

        if ($token === false) {
            return new ApiProblem(StatusCodeInterface::STATUS_UNAUTHORIZED, 'invalid-token');
        }

        $token = $this->authenticationService->withToken($token->getFieldValue(), false);
        if (is_string($token) || !isset($token['userId'])) {
            return new ApiProblem(StatusCodeInterface::STATUS_UNAUTHORIZED, 'invalid-token');
        }

        try {
            $sharedSpaceId = $token['sharedSpaceId'] ?? null;

            if ($sharedSpaceId !== null) {
                $this->sharedSpaceService->deleteAccount($sharedSpaceId, $token['userId']);
                $result = true;
            } else {
                $result = $this->userService->delete($token['userId']);
            }

            if ($result instanceof ApiProblem) {
                return $result;
            } elseif ($result === true) {
                return new NoContentResponse();
            }

            // If we get here...
            return new ApiProblem(500, 'Unable to process request');
        } catch (\Throwable $e) {
            $this->logger->error('Error deleting user', ['exception' => $e]);

            return new ApiProblem(500, 'Unable to process request');
        }
    }
}
