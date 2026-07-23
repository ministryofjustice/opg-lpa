<?php

declare(strict_types=1);

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\SharedSpace\SharedSpaceService as Service;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\Logging\LoggerTrait;
use Throwable;
use Traversable;

class SharedSpaceController extends AbstractAuthController
{
    use LoggerTrait;

    private ?ApplicationsService $applicationsService = null;

    public function setApplicationsService(ApplicationsService $applicationsService): void
    {
        $this->applicationsService = $applicationsService;
    }

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

        $data = $this->getBodyContent();

        if (!isset($data['name']) || trim((string) $data['name']) === '') {
            return new ApiProblem(400, 'A name must be passed for the shared space');
        }

        try {
            $result = $this->getService()->create(trim((string) $data['name']), $userId);
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
}
