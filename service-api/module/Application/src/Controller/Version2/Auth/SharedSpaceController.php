<?php

declare(strict_types=1);

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Library\Http\Response\Json;
use Application\Model\Entity\MemberInvite;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\SharedSpace\MemberNotInSharedSpaceException;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use DateInterval;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\MvcEvent;
use MakeShared\DataModel\Lpa\Lpa;
use Throwable;
use Traversable;

class SharedSpaceController extends AbstractRestfulController
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly SharedSpaceService $sharedSpaceService,
        private readonly ApplicationsService $applicationsService,
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
            return new ApiProblem(StatusCodeInterface::STATUS_UNAUTHORIZED, 'invalid-token');
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), false);

        if (is_string($result) || !isset($result['userId'])) {
            return new ApiProblem(StatusCodeInterface::STATUS_UNAUTHORIZED, 'invalid-token');
        }

        $userId = $result['userId'];

        $data = $this->processBodyContent($this->getRequest());

        if (!isset($data['name']) || trim((string) $data['name']) === '') {
            return new ApiProblem(StatusCodeInterface::STATUS_BAD_REQUEST, 'A name must be passed for the shared space');
        }

        try {
            $result = $this->sharedSpaceService->create(trim((string) $data['name']), $userId);
        } catch (UserAlreadyInSharedSpaceException $e) {
            return new ApiProblem(StatusCodeInterface::STATUS_BAD_REQUEST, 'user-already-in-shared-space');
        } catch (Throwable $e) {
            return new ApiProblem(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, 'Unable to process request ' . $e->getMessage());
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
        $result = $this->checkToken();
        if ($result instanceof ApiProblem) {
            return $result;
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

    /**
     * Returns a single member's details within the requesting user's shared
     * space. Only an admin member of the shared space may access this, since
     * it's used to render the permissions-management form for that member.
     *
     * @return Json|ApiProblem
     */
    public function memberAction(): Json|ApiProblem
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        $token = $this->checkToken();
        if ($token instanceof ApiProblem) {
            return $token;
        }

        if (!$this->sharedSpaceService->isAdmin($token['sharedSpaceId'], $token['userId'])) {
            return new ApiProblem(StatusCodeInterface::STATUS_FORBIDDEN, 'Access Denied');
        }

        $memberUserId = $this->params()->fromRoute('memberUserId');

        $member = $this->sharedSpaceService->getMember($token['sharedSpaceId'], $memberUserId);

        if ($member === null) {
            return new ApiProblem(StatusCodeInterface::STATUS_NOT_FOUND, 'Member not found');
        }

        return new Json(['member' => $member]);
    }

    public function membersAndInvitesAction(): Json|ApiProblem
    {
        $result = $this->checkToken();
        if ($result instanceof ApiProblem) {
            return $result;
        }

        $response = [
            'members' => $this->sharedSpaceService->getMembers($result['sharedSpaceId']),
            'invites' => $this->sharedSpaceService->getInvites($result['sharedSpaceId']),
        ];

        return new Json($response);
    }

    public function inviteAction(): Json|ApiProblem
    {
        $result = $this->checkToken();
        if ($result instanceof ApiProblem) {
            return $result;
        }

        if (!$this->sharedSpaceService->isAdmin($result['sharedSpaceId'], $result['userId'])) {
            return new ApiProblem(StatusCodeInterface::STATUS_FORBIDDEN, 'Access Denied');
        }

        $data = $this->processBodyContent($this->getRequest());

        $code = sprintf('%08d', random_int(0, 99999999));
        $created = new DateTimeImmutable('now');

        try {
            $params = $this->sharedSpaceService->invite(new MemberInvite(
                $result['userId'],
                $result['sharedSpaceId'],
                $data['firstNames'],
                $data['lastName'],
                $data['email'],
                $data['isAdmin'],
                $code,
                $created,
                $created->add(DateInterval::createFromDateString('7 days')),
            ));
        } catch (Throwable $e) {
            return new ApiProblem(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, 'Unable to process request ' . $e->getMessage());
        }

        return new Json($params);
    }

    public function revokeInviteAction(): Json|ApiProblem
    {
        $result = $this->checkToken();
        if ($result instanceof ApiProblem) {
            return $result;
        }

        if (!$this->sharedSpaceService->isAdmin($result['sharedSpaceId'], $result['userId'])) {
            return new ApiProblem(StatusCodeInterface::STATUS_FORBIDDEN, 'Access Denied');
        }

        $inviteId = (int) $this->params()->fromRoute('memberInviteId');

        try {
            $this->sharedSpaceService->revokeInvite($result['sharedSpaceId'], $inviteId, $result['userId']);
        } catch (Throwable $e) {
            return new ApiProblem(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, 'Unable to process request ' . $e->getMessage());
        }

        return new Json(['success' => true]);
    }

    /**
     * Adds an existing user as a member of the requesting user's shared space.
     *
     * @return Json|ApiProblem
     */
    public function addMemberAction(): Json|ApiProblem
    {
        $result = $this->checkToken();
        if ($result instanceof ApiProblem) {
            return $result;
        }

        $data = $this->processBodyContent($this->getRequest());

        try {
            $this->sharedSpaceService->addMember(
                $result['sharedSpaceId'],
                trim((string) $data['userIdToAdd']),
                $result['userId'],
                (bool) $data['isAdmin'],
            );
        } catch (UserAlreadyInSharedSpaceException $e) {
            return new ApiProblem(StatusCodeInterface::STATUS_BAD_REQUEST, 'user-already-in-shared-space');
        } catch (Throwable $e) {
            return new ApiProblem(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, 'Unable to process request ' . $e->getMessage());
        }

        return new Json(['success' => true], StatusCodeInterface::STATUS_CREATED);
    }

    /**
     * Updates a member's admin permission within the requesting user's
     * shared space. Only an admin member of the shared space (i.e. whose
     * auth token resolves to this shared space, and who has admin
     * permissions within it) may access this.
     *
     * @return Json|ApiProblem
     */
    public function updateMemberAction(): Json|ApiProblem
    {
        $result = $this->checkToken();
        if ($result instanceof ApiProblem) {
            return $result;
        }

        $memberUserId = $this->params()->fromRoute('memberUserId');

        if (!$this->sharedSpaceService->isAdmin($result['sharedSpaceId'], $result['userId'])) {
            return new ApiProblem(StatusCodeInterface::STATUS_FORBIDDEN, 'Access Denied');
        }

        $data = $this->processBodyContent($this->getRequest());

        try {
            $this->sharedSpaceService->updateMember(
                $result['sharedSpaceId'],
                $memberUserId,
                (bool)$data['isAdmin'],
                (bool)$data['isActive'],
            );
        } catch (MemberNotInSharedSpaceException $e) {
            return new ApiProblem(StatusCodeInterface::STATUS_NOT_FOUND, $e->getMessage());
        } catch (Throwable $e) {
            return new ApiProblem(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, 'Unable to process request ' . $e->getMessage());
        }

        return new Json(['success' => true]);
    }

    private function checkToken(): array|ApiProblem
    {
        // Suppress psalm errors caused by bug in laminas-mvc;
        // see https://github.com/laminas/laminas-mvc/issues/77
        /** @psalm-suppress UndefinedInterfaceMethod */
        $token = $this->getRequest()->getHeader('Token');
        if ($token === false) {
            return new ApiProblem(StatusCodeInterface::STATUS_UNAUTHORIZED, 'invalid-token');
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), false);
        if (is_string($result) || !isset($result['userId'])) {
            return new ApiProblem(StatusCodeInterface::STATUS_UNAUTHORIZED, 'invalid-token');
        }
        if (empty($result['sharedSpaceId'])) {
            return new ApiProblem(StatusCodeInterface::STATUS_FORBIDDEN, 'Access Denied');
        }

        return $result;
    }
}
