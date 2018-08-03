<?php

namespace Application\Model\Service\Applications;

use Application\Model\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Library\Random\Csprng;
use Application\Model\Service\AbstractService;
use Application\Model\Service\DataModelEntity;
use MongoDB\BSON\UTCDateTime;
use Opg\Lpa\DataModel\Lpa\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Paginator\Adapter\Callback as PaginatorCallback;
use Zend\Paginator\Adapter\NullFill as PaginatorNull;
use RuntimeException;

class Service extends AbstractService
{
    /**
     * @param $data
     * @param $userId
     * @return DataModelEntity
     */
    public function create($data, $userId)
    {
        // If no data was passed, represent with an empty array.
        if (is_null($data)) {
            $data = [];
        }

        // Generate an id for the LPA
        $csprng = new Csprng();

        //  Generate a random 11-digit number to use as the LPA id - this loops until we find one that's 'free'.
        do {
            $id = $csprng->GetInt(1000000, 99999999999);

            // Check if the id already exists. We're looking for a value of null.
            $exists = $this->apiLpaCollection->findOne([
                '_id' => $id
            ], [
                '_id' => true
            ]);
        } while (!is_null($exists));

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

        $this->apiLpaCollection->insertOne($lpa->toArray(new DateCallback()));

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
     * @param $data
     * @param $id
     * @param $userId
     * @return ValidationApiProblem|DataModelEntity
     */
    public function patch($data, $id, $userId)
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
    public function fetch($id, $userId)
    {
        // Note: user has to match
        $result = $this->apiLpaCollection->findOne([
            '_id' => (int) $id,
            'user' => $userId
        ]);

        if (is_null($result)) {
            return new ApiProblem(404, 'Document ' . $id . ' not found for user ' . $userId);
        }

        $result = ['id' => $result['_id']] + $result;

        $lpa = new Lpa($result);

        return new DataModelEntity($lpa);
    }

    /**
     * @param $userId
     * @param array $params
     * @return Collection
     */
    public function fetchAll($userId, $params = [])
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
                $filter['_id'] = (int)$search;
            } else {
                // If it starts with an A and everything that follows after is numeric...
                if (substr(strtoupper($search), 0, 1) == 'A' && is_numeric($ident = preg_replace('/\s+/', '', substr($search, 1)))) {
                    // Assume it's an LPA id.
                    $filter['_id'] = (int)$ident;
                } elseif (strlen($search) >= 3) {
                    // Otherwise assume it's a name, and only search if 3 chars or longer
                    $filter['search'] = [
                        '$regex' => '.*' . $search . '.*',
                        '$options' => 'i',
                    ];
                }
            }
        }

        $count = $this->apiLpaCollection->count($filter);

        // If there are no records, just return an empty paginator...
        if ($count == 0) {
            return new Collection(new PaginatorNull);
        }

        // Map the results into a Zend Paginator, lazely converting them to LPA instances as we go...
        $apiLpaCollection = $this->apiLpaCollection;

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

                $cursor = $apiLpaCollection->find($filter, $options);
                $lpas = $cursor->toArray();

                // Convert the results to instances of the LPA object..
                $items = array_map(function ($lpa) {
                    $lpa = [ 'id' => $lpa['_id'] ] + $lpa;

                    return new Lpa($lpa);
                }, $lpas);

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
     * @return ApiProblem|bool
     */
    public function delete($id, $userId)
    {
        $filter = [
            '_id' => (int) $id,
            'user' => $userId,
        ];

        $result = $this->apiLpaCollection->findOne($filter, ['projection' => ['_id' => true]]);

        if (is_null($result)) {
            return new ApiProblem(404, 'Document not found');
        }

        //  We don't want to remove the document entirely as we need to make sure the same ID isn't reassigned.
        //  So we just strip the document down to '_id' and 'updatedAt'.
        $result['updatedAt'] = new UTCDateTime();

        $this->apiLpaCollection->replaceOne($filter, $result);

        return true;
    }

    /**
     * @param $userId
     * @return bool
     */
    public function deleteAll($userId)
    {
        $query = ['user' => $userId];

        $lpas = $this->apiLpaCollection->find($query, ['_id' => true]);

        foreach ($lpas as $lpa) {
            $this->delete($lpa['_id'], $userId);
        }

        return true;
    }
}
