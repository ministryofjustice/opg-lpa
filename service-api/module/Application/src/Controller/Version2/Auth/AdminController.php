<?php

declare(strict_types=1);

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Model\Service\Applications\Service;
use Application\Model\Service\Users\Service as UsersService;
use Laminas\Mvc\Controller\AbstractRestfulController;
use MakeShared\DataModel\Lpa\Lpa;
use Traversable;

class AdminController extends AbstractRestfulController
{
    public function __construct(
        private UsersService $usersService,
        private Service $applicationsService
    ) {
    }

    /**
     * Search action for user details
     * NOTE: Custom action method has been used here because 'get' can not be used without an ID value in the URL target
     */
    public function searchUsersAction(): Json|ApiProblem
    {
        $queryParams = $this->params()->fromQuery();

        if (isset($queryParams['aReference'])) {
            $user = $this->usersService->searchByAReference($queryParams['aReference']);

            if ($user === false) {
                return new ApiProblem(404, 'No user found with supplied A Reference');
            }

            return new Json($user);
        }

        $user = $this->usersService->searchByUsername($queryParams['email']);

        if ($user === false) {
            return new ApiProblem(404, 'No user found with supplied email address');
        }

        return new Json($user);
    }

    /**
     * Match action for user details (wildcard/case-insensitive search)
     */
    public function matchUsersAction(): Json
    {
        $params = $this->params();
        $query = $params->fromQuery('query');

        $options = [
            'offset' => $params->fromQuery('offset', 0),
            'limit' => $params->fromQuery('limit', 10)
        ];

        $users = $this->usersService->matchUsers($query, $options);

        return new Json((array) $users);
    }

    public function sharedSpaceLpasAction(): Json|ApiProblem
    {
        $sharedSpaceId = $this->params()->fromRoute('sharedSpaceId');
        $query = $this->params()->fromQuery();
        $page = $query['page'] ?? null;
        $perPage = $query['perPage'] ?? null;

        if (empty($sharedSpaceId)) {
            return new ApiProblem(400, 'Missing required parameter: sharedSpaceId');
        }

        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        } else {
            $page = intval($page);
        }

        $filteredQuery = $query;
        unset($filteredQuery['page']);
        unset($filteredQuery['perPage']);

        $paginator = $this->applicationsService->fetchAllForSharedSpace($sharedSpaceId, $filteredQuery);

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
