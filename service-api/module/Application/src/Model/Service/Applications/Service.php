<?php

namespace Application\Model\Service\Applications;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use Laminas\Db\Sql\Predicate\PredicateInterface;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Adapter\Callback as PaginatorCallback;
use Laminas\Paginator\Paginator;
use MakeShared\DataModel\Lpa\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * @psalm-suppress PossiblyUnusedMethod Called dynamically in ServiceAbstractFactory
     */
    public function __construct(private readonly SharedSpaceRepositoryInterface $sharedSpaceRepository)
    {
    }

    /**
     * @param $data
     * @param $userId
     * @return DataModelEntity
     */
    public function create($data, string $userId)
    {
        // If no data was passed, represent with an empty array.
        if (is_null($data)) {
            $data = [];
        }

        // If the user belongs to a shared space, newly created LPAs are
        // owned by that shared space from the outset; 'user' always
        // records the actual creating user (see ownerPredicate() and
        // sharedSpacePredicate()).
        $sharedSpaceId = $this->sharedSpaceRepository->getSharedSpaceIdForUser($userId);

        /*
         * A loop is used here to catch any ID clashes. If such a clash happens, a different ID will be tried.
         */
        do {
            $id = random_int(1000000, 99999999999);

            $lpa = new Lpa([
                'id'                => $id,
                'startedAt'         => new MillisecondDateTime(),
                'updatedAt'         => new MillisecondDateTime(),
                'user'              => $userId,
                'sharedSpaceId'     => $sharedSpaceId,
                'locked'            => false,
                'whoAreYouAnswered' => false,
                'document'          => new Document\Document(),
                'updatedBy'         => $userId,
                'version'           => 1,
            ]);

            $data = $this->filterIncomingData($data);

            if (!empty($data)) {
                $lpa->populate($data);
            }

            $this->assertLpaValid($lpa, 'during LPA create');

            $created = $this->getApplicationRepository()->insert($lpa);
        } while (!$created);

        $entity = new DataModelEntity($lpa);

        return $entity;
    }

    /**
     * @param array $data
     * @return array
     */
    private function filterIncomingData(array $data)
    {
        return array_intersect_key($data, array_flip([
            'document',
            'metadata',
            'payment',
            'repeatCaseNumber'
        ]));
    }

    /**
     * @param array[] $data
     * @param $id
     * @param $userId
     *
     * @return ValidationApiProblem|DataModelEntity
     *
     * @psalm-param array{metadata: array} $data
     */
    public function patch(array $data, string $id, string $userId)
    {

        /** @var Lpa $lpa */
        $lpa = $this->fetch($id, $userId)->getData();

        $data = $this->filterIncomingData($data);

        if (!empty($data)) {
            $lpa->populate($data);
        }

        $validation = $lpa->validate();

        if ($validation->hasErrors()) {
            $this->log('warning', 'LPA validation failed during patch', [
                'lpaid' => $id,
                'userId' => $userId,
                'validation_errors' => $validation->getArrayCopy(),
                'patched_keys' => array_keys($data),
            ]);

            return new ValidationApiProblem($validation);
        }

        $this->updateLpa($lpa);

        return new DataModelEntity($lpa);
    }

    /**
     * @param $id
     * @param $userId
     * @return ApiProblem|DataModelEntity
     */
    public function fetch(string $id, string $userId)
    {
        // Note: user (or their shared space) has to match
        $sharedSpaceId = $this->sharedSpaceRepository->getSharedSpaceIdForUser($userId);
        $result = $this->getApplicationRepository()->getById((int) $id, $userId, $sharedSpaceId);

        if (is_null($result)) {
            return new ApiProblem(404, 'Document ' . $id . ' not found for user ' . $userId);
        }

        $lpa = new Lpa($result);

        return new DataModelEntity($lpa);
    }

    /**
     * Fetch LPAs with the specified $lpaIds, providing they are owned by
     * the user with given $userId (or the shared space they belong to, if
     * any). If an LPA is requested which is not owned by the user (or their
     * shared space), that record is not returned.
     *
     * @param array $lpaIds : IDs of LPAs to fetch
     * @param string $userId : restrict results to this user ID (or their shared space)
     * @return Lpa[]
     */
    public function filterByIdsAndUser(array $lpaIds, string $userId): array
    {
        $sharedSpaceId = $this->sharedSpaceRepository->getSharedSpaceIdForUser($userId);
        $records = $this->getApplicationRepository()->getByIdsAndUserOrSharedSpace($lpaIds, $userId, $sharedSpaceId);
        $lpas = [];
        foreach ($records as $record) {
            $lpas[] = new Lpa($record);
        }
        return $lpas;
    }

    /**
     * Fetch the individually-owned LPAs for $userId. This deliberately does
     * NOT resolve shared space membership - use fetchAllForSharedSpace() to
     * list a shared space's LPAs instead. Keeping the two separate ensures
     * this endpoint can never return another shared space member's LPAs to
     * a caller whose own identity is stale (e.g. hasn't yet been refreshed
     * to reflect a shared space they've just joined or left).
     *
     * @param $userId
     * @param array $params
     * @return Paginator<int, Lpa>
     */
    public function fetchAll(string $userId, $params = [])
    {
        return $this->paginate(
            $this->getApplicationRepository()->ownerPredicate($userId),
            $params,
            false,
        );
    }

    /**
     * Fetch all LPAs owned by a shared space, regardless of which member
     * created them.
     *
     * @param string $sharedSpaceId
     * @param array $params
     * @return Paginator<int, Lpa>
     */
    public function fetchAllForSharedSpace(string $sharedSpaceId, array $params = [])
    {
        return $this->paginate(
            $this->getApplicationRepository()->sharedSpacePredicate($sharedSpaceId),
            $params,
            true,
        );
    }

    /**
     * @return Paginator<int, Lpa>
     */
    private function paginate(PredicateInterface $ownerPredicate, array $params, bool $forSharedSpace)
    {
        $filter = [
            $ownerPredicate,
        ];

        //  Merge in any filter requirements...
        if (isset($params['filter']) && is_array($params['filter'])) {
            $filter = array_merge($params, $filter);
        }

        //  If we have a search query...
        if (isset($params['search']) && strlen(trim($params['search'])) > 0) {
            $search = trim($params['search']);

            // If the string is numeric, assume it's an LPA id.
            if (is_numeric($search)) {
                $filter['id'] = (int)$search;
            } else {
                // If it starts with an A and everything that follows after is numeric...
                if (substr(strtoupper($search), 0, 1) == 'A' && is_numeric($ident = preg_replace('/\s+/', '', substr($search, 1)))) {
                    // Assume it's an LPA id.
                    $filter['id'] = (int)$ident;
                } elseif (strlen($search) >= 3) {
                    // Otherwise assume it's a name, and only search if 3 chars or longer
                    $filter['search'] = $search;
                }
            }
        }

        // Get the total number of results
        $count = $this->getApplicationRepository()->count($filter);

        // If there are no records, just return an empty paginator...
        if ($count == 0) {
            return new Paginator(new ArrayAdapter());
        }

        // Map the results into a Zend Paginator, lazely converting them to LPA instances as we go...
        $apiLpaCollection = $this->getApplicationRepository();

        $callback = new PaginatorCallback(
            function ($offset, $itemCountPerPage) use ($apiLpaCollection, $filter, $forSharedSpace) {
                // getItems callback
                $options = [
                    'sort' => [
                        'updatedAt' => -1
                    ],
                    'skip' => $offset,
                    'limit' => $itemCountPerPage
                ];

                $cursor = $forSharedSpace
                    ? $apiLpaCollection->fetchForSharedSpace($filter, $options)
                    : $apiLpaCollection->fetch($filter, $options);

                // Convert the results to instances of the LPA object..
                $items = array_map(function ($lpa) {
                    return new Lpa($lpa);
                }, iterator_to_array($cursor, false));

                return $items;
            },
            function () use ($count) {
                // count callback
                return $count;
            }
        );

        return new Paginator($callback);
    }

    /**
     * @param $id
     * @param $userId
     * @return ApiProblem|bool
     */
    public function delete($id, string $userId)
    {
        $sharedSpaceId = $this->sharedSpaceRepository->getSharedSpaceIdForUser($userId);

        $result = $this->getApplicationRepository()->getById((int) $id, $userId, $sharedSpaceId);

        if (is_null($result)) {
            return new ApiProblem(404, 'Document not found');
        }

        $this->getApplicationRepository()->deleteById($id, $userId, $sharedSpaceId);

        return true;
    }

    /**
     * Delete all LPAs directly owned by this user.
     *
     * Note: deliberately does NOT include LPAs owned by a shared space the
     * user belongs to - deleting one member's account must not delete LPAs
     * that other members of the shared space still need access to.
     *
     * @param $userId
     */
    public function deleteAll($userId): void
    {
        $lpas = $this->getApplicationRepository()->fetchByUserId($userId);

        foreach ($lpas as $lpa) {
            $this->getApplicationRepository()->deleteById($lpa['id'], $userId);
        }
    }
}
