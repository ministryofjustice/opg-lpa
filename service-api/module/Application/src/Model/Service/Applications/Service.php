<?php

namespace Application\Model\Service\Applications;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use MakeShared\DataModel\Lpa\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Laminas\Paginator\Adapter\Callback as PaginatorCallback;
use Laminas\Paginator\Adapter\NullFill as PaginatorNull;
use RuntimeException;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;

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

        /*
         * A loop is used here to catch any ID clashes. If such a clash happens, a different ID will be tried.
         */
        do {
            $id = random_int(1000000, 99999999999);

            $lpa = new Lpa([
                'id'                => $id,
                'startedAt'         => new DateTime(),
                'updatedAt'         => new DateTime(),
                'user'              => $userId,
                'locked'            => false,
                'whoAreYouAnswered' => false,
                'document'          => new Document\Document(),
            ]);

            $data = $this->filterIncomingData($data);

            if (!empty($data)) {
                $lpa->populate($data);
            }

            if ($lpa->validate()->hasErrors()) {
                throw new RuntimeException('A malformed LPA object was created');
            }

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
    public function patch(array $data, $id, string $userId)
    {

        /** @var Lpa $lpa */
        $lpa = $this->fetch($id, $userId)->getData();

        $data = $this->filterIncomingData($data);

        if (!empty($data)) {
            $lpa->populate($data);
        }

        $validation = $lpa->validate();

        if ($validation->hasErrors()) {
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
    public function fetch($id, string $userId)
    {
        // Note: user has to match
        $result = $this->getApplicationRepository()->getById((int) $id, $userId);

        if (is_null($result)) {
            return new ApiProblem(404, 'Document ' . $id . ' not found for user ' . $userId);
        }

        $lpa = new Lpa($result);

        return new DataModelEntity($lpa);
    }

    /**
     * Fetch LPAs with the specified $lpaIds, providing they are owned by
     * the user with given $userId. If an LPA is requested which is not owned
     * by the user, that record is not returned.
     *
     * @param array $lpaIds : IDs of LPAs to fetch
     * @param string $userId : restrict results to this user ID
     *
     * @return Lpa[]
     *
     * @psalm-return list{0?: Lpa,...}
     */
    public function filterByIdsAndUser(array $lpaIds, string $userId): array
    {
        $records = $this->getApplicationRepository()->getByIdsAndUser($lpaIds, $userId);
        $lpas = [];
        foreach ($records as $record) {
            $lpas[] = new Lpa($record);
        }
        return $lpas;
    }

    /**
     * @param $userId
     * @param array $params
     * @return Collection
     */
    public function fetchAll(string $userId, $params = [])
    {
        $filter = [
            'user' => $userId
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
            return new Collection(new PaginatorNull());
        }

        // Map the results into a Zend Paginator, lazely converting them to LPA instances as we go...
        $apiLpaCollection = $this->getApplicationRepository();

        $callback = new PaginatorCallback(
            function ($offset, $itemCountPerPage) use ($apiLpaCollection, $filter) {
                // getItems callback
                $options = [
                    'sort' => [
                        'updatedAt' => -1
                    ],
                    'skip' => $offset,
                    'limit' => $itemCountPerPage
                ];

                $cursor = $apiLpaCollection->fetch($filter, $options);

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

        return new Collection($callback);
    }

    /**
     * @param $id
     * @param $userId
     *
     * @return ApiProblem|true
     */
    public function delete($id, string $userId): bool|ApiProblem
    {
        $result = $this->getApplicationRepository()->getById((int) $id, $userId);

        if (is_null($result)) {
            return new ApiProblem(404, 'Document not found');
        }

        $this->getApplicationRepository()->deleteById($id, $userId);

        return true;
    }

    /**
     * @param $userId
     *
     * @return true
     */
    public function deleteAll($userId): bool
    {
        $lpas = $this->getApplicationRepository()->fetchByUserId($userId);

        foreach ($lpas as $lpa) {
            $this->delete($lpa['id'], $userId);
        }

        return true;
    }
}
