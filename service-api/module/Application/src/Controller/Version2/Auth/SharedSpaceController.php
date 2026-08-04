<?php

declare(strict_types=1);

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Library\Http\Response\Json;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use MakeShared\DataModel\Lpa\Lpa;
use Throwable;
use Traversable;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\MvcEvent;

class SharedSpaceController extends AbstractRestfulController
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly SharedSpaceService $sharedSpaceService,
        private readonly ApplicationsService $applicationsService
    ) {
    }

    /**
     * AbstractRestfulController doesn't know how to turn a bare ApiProblem
     * value returned from an action into a Response with the correct HTTP
     * status code - without this, ApiProblem responses (e.g. 400/401/403/500)
     * are sent to clients as a 200 with a malformed body. See
     * AbstractAuthController::onDispatch() for the equivalent used by
     * controllers that extend it.
     *
     * @return mixed|ApiProblemResponse
     */
    public function onDispatch(MvcEvent $e)
    {
        $return = parent::onDispatch($e);

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;
    }

    /**
     * Creates a shared space and moves ownership of the requesting user's
     * LPAs into it.
     *
     * @return Json|ApiProblem
     */
    public function createAction(): Json|ApiProblem
    {
        // Suppress psalm errors caused by bug in laminas-mvc;
        // see https://github.com/laminas/laminas-mvc/issues/77
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $token = $this->getRequest()->getHeader('Token');

        if ($token === false) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), false);

        if (is_string($result) || !isset($result['userId'])) {
            return new ApiProblem(401, 'invalid-token');
        }

        $userId = $result['userId'];

        $data = $this->processBodyContent($this->getRequest());

        if (!isset($data['name']) || trim((string) $data['name']) === '') {
            return new ApiProblem(400, 'A name must be passed for the shared space');
        }

        try {
            $result = $this->sharedSpaceService->create(trim((string) $data['name']), $userId);
        } catch (UserAlreadyInSharedSpaceException $e) {
            return new ApiProblem(400, 'user-already-in-shared-space');
        } catch (Throwable $e) {
            return new ApiProblem(500, 'Unable to process request ' . $e->getMessage());
        }

        return new Json($result);
    }

    /**
     * Lists the LPAs owned by a shared space, paginated. Only members of
     * the shared space (i.e. whose auth token resolves to this shared
     * space) may access this.
     *
     * @return Json|ApiProblem
     */
    public function lpasAction(): Json|ApiProblem
    {
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $token = $this->getRequest()->getHeader('Token');

        if ($token === false) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), false);

        if (is_string($result) || !isset($result['userId'])) {
            return new ApiProblem(401, 'invalid-token');
        }

        if (empty($result['sharedSpaceId'])) {
            return new ApiProblem(403, 'Access Denied');
        }

        $query = $this->params()->fromQuery();
        $page = $query['page'] ?? null;
        $perPage = $query['perPage'] ?? null;

        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        } else {
            $page = intval($page);
        }

        $filteredQuery = $query;
        unset($filteredQuery['page']);
        unset($filteredQuery['perPage']);

        $paginator = $this->applicationsService->fetchAllForSharedSpace($result['sharedSpaceId'], $filteredQuery);

        $paginator->setCurrentPageNumber($page);

        if (is_numeric($perPage) && $perPage > 0) {
            $paginator->setItemCountPerPage(intval($perPage));
        }

        /** @var Traversable<int, Lpa> $items */
        $items = $paginator->getCurrentItems();

        $response = [
            'applications' => array_map(
                fn (Lpa $lpa) => $lpa->toArray(),
                iterator_to_array($items)
            ),
            'total' => $paginator->getTotalItemCount(),
        ];

        return new Json($response);
    }

    public function membersAction(): Json|ApiProblem
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        $token = $this->getRequest()->getHeader('Token');
        if ($token === false) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), false);
        if (is_string($result) || !isset($result['userId'])) {
            return new ApiProblem(401, 'invalid-token');
        }
        if (empty($result['sharedSpaceId'])) {
            return new ApiProblem(403, 'Access Denied');
        }

        $response = [
            'members' => $this->sharedSpaceService->getMembers($result['sharedSpaceId']),
        ];

        return new Json($response);
    }

    /**
     * Adds an existing user as a member of the requesting user's shared space.
     *
     * @return Json|ApiProblem
     */
    public function addMemberAction(): Json|ApiProblem
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        $token = $this->getRequest()->getHeader('Token');
        if ($token === false) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), false);
        if (is_string($result) || !isset($result['userId'])) {
            return new ApiProblem(401, 'invalid-token');
        }
        if (empty($result['sharedSpaceId'])) {
            return new ApiProblem(403, 'Access Denied');
        }

        $data = $this->processBodyContent($this->getRequest());

        if (!isset($data['userIdToAdd']) || !isset($data['isAdmin']) || trim((string) $data['userIdToAdd']) === '') {
            return new ApiProblem(400, 'A userIdToAdd must be passed to add a member to the shared space');
        }

        try {
            $this->sharedSpaceService->addMember(
                $result['sharedSpaceId'],
                trim((string) $data['userIdToAdd']),
                $result['userId'],
                (bool) $data['isAdmin'],
            );
        } catch (UserAlreadyInSharedSpaceException $e) {
            return new ApiProblem(400, 'user-already-in-shared-space');
        } catch (Throwable $e) {
            return new ApiProblem(500, 'Unable to process request ' . $e->getMessage());
        }

        return new Json(['success' => true], 201);
    }

    /**
     * Updates a member's admin permission within the requesting user's
     * shared space. Only members of the shared space (i.e. whose auth
     * token resolves to this shared space) may access this.
     *
     * @return Json|ApiProblem
     */
    public function updateMemberAction(): Json|ApiProblem
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        $token = $this->getRequest()->getHeader('Token');
        if ($token === false) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), false);
        if (is_string($result) || !isset($result['userId'])) {
            return new ApiProblem(401, 'invalid-token');
        }
        if (empty($result['sharedSpaceId'])) {
            return new ApiProblem(403, 'Access Denied');
        }

        $memberUserId = $this->params()->fromRoute('memberUserId');

        $data = $this->processBodyContent($this->getRequest());

        if (!isset($data['isAdmin'])) {
            return new ApiProblem(400, 'An isAdmin value must be passed to update a shared space member');
        }

        try {
            $updated = $this->sharedSpaceService->updateMemberIsAdmin(
                $result['sharedSpaceId'],
                $memberUserId,
                (bool) $data['isAdmin'],
            );
        } catch (Throwable $e) {
            return new ApiProblem(500, 'Unable to process request ' . $e->getMessage());
        }

        if (!$updated) {
            return new ApiProblem(404, 'Member not found in shared space');
        }

        return new Json(['success' => true]);
    }
}
