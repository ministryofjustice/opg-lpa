<?php

declare(strict_types=1);

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Model\Service\SharedSpace\SharedSpaceService as Service;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use MakeShared\Logging\LoggerTrait;

class SharedSpaceController extends AbstractAuthController
{
    use LoggerTrait;

    /**
     * Get the service to use
     *
     * @return Service
     */
    protected function getService()
    {
        return $this->service;
    }

    /**
     * Creates a shared space and moves ownership of the requesting user's
     * LPAs into it.
     *
     * @return Json|ApiProblem
     */
    public function createAction()
    {
        $data = $this->getBodyContent();

        if (!isset($data['name']) || trim((string) $data['name']) === '') {
            return new ApiProblem(400, 'A name must be passed for the shared space');
        }

        $userId = $this->getAuthenticatedUserId();

        if ($userId === null) {
            return new ApiProblem(401, 'invalid-token');
        }

        try {
            $result = $this->getService()->create(trim((string) $data['name']), $userId);
        } catch (UserAlreadyInSharedSpaceException $e) {
            return new ApiProblem(400, 'user-already-in-shared-space');
        }

        $this->getLogger()->info('Shared space created', $result);

        return new Json($result);
    }

    /**
     * Determine the ID of the user making the request, from the Token header.
     *
     * @return string|null Null if there is no valid Token header
     */
    private function getAuthenticatedUserId(): ?string
    {
        // Suppress psalm errors caused by bug in laminas-mvc;
        // see https://github.com/laminas/laminas-mvc/issues/77
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $token = $this->getRequest()->getHeader('Token');

        if ($token === false) {
            return null;
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), false);

        if (is_string($result) || !isset($result['userId'])) {
            return null;
        }

        return $result['userId'];
    }
}
