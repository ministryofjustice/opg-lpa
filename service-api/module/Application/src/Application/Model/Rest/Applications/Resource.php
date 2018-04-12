<?php

namespace Application\Model\Rest\Applications;

use Application\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Library\Random\Csprng;
use Application\Model\Rest\AbstractResource;
use MongoDB\BSON\UTCDateTime;
use Opg\Lpa\DataModel\Lpa\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Paginator\Adapter\Callback as PaginatorCallback;
use Zend\Paginator\Adapter\NullFill as PaginatorNull;
use RuntimeException;

/**
 * Application Resource
 *
 * Class Resource
 * @package Application\Model\Rest\Applications
 */
class Resource extends AbstractResource
{
    /**
     * @param $data
     * @return Entity
     */
    public function create($data)
    {
        $this->checkAccess();

        // If no data was passed, represent with an empty array.
        if (is_null($data)) {
            $data = array();
        }

        // Generate an id for the LPA
        $csprng = new Csprng();

        //  Generate a random 11-digit number to use as the LPA id - this loops until we find one that's 'free'.
        do {
            $id = $csprng->GetInt(1000000, 99999999999);

            // Check if the id already exists. We're looking for a value of null.
            $exists = $this->lpaCollection->findOne([
                '_id' => $id
            ], [
                '_id' => true
            ]);
        } while (!is_null($exists));

        $lpa = new Lpa([
            'id'                => $id,
            'startedAt'         => new DateTime(),
            'updatedAt'         => new DateTime(),
            'user'              => $this->routeUser->userId(),
            'locked'            => false,
            'whoAreYouAnswered' => false,
            'document'          => new Document\Document(),
        ]);

        $data = $this->filterIncomingData($data);

        if (!empty($data)) {
            $lpa->populate($data);
        }

        if ($lpa->validate()->hasErrors()) {
            //  This is not based on user input (we already validated the Document above),
            //  thus if we have errors here it is our fault!
            throw new RuntimeException('A malformed LPA object was created');
        }

        $this->lpaCollection->insertOne($lpa->toArray(new DateCallback()));

        $entity = new Entity($lpa);

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
     * @return ValidationApiProblem|Entity
     */
    public function patch($data, $id)
    {
        $this->checkAccess();

        $lpa = $this->fetch($id)->getLpa();

        $data = $this->filterIncomingData($data);

        if (!empty($data)) {
            $lpa->populate($data);
        }

        $validation = $lpa->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        $this->updateLpa($lpa);

        return new Entity($lpa);
    }

    /**
     * @param $id
     * @return ApiProblem|Entity
     */
    public function fetch($id)
    {
        $this->checkAccess();

        // Note: user has to match
        $userId = $this->routeUser->userId();
        $result = $this->lpaCollection->findOne([
            '_id' => (int) $id,
            'user' => $userId
        ]);

        if (is_null($result)) {
            return new ApiProblem(404, 'Document ' . $id . ' not found for user ' . $this->routeUser->userId());
        }

        $result = ['id' => $result['_id']] + $result;

        $lpa = new Lpa($result);

        $entity = new Entity($lpa);

        return $entity;
    }

    /**
     * @param array $params
     * @return Collection
     */
    public function fetchAll($params = [])
    {
        $this->checkAccess();

        $filter = [
            'user' => $this->routeUser->userId()
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

        $count = $this->lpaCollection->count($filter);

        // If there are no records, just return an empty paginator...
        if ($count == 0) {
            return new Collection(new PaginatorNull, $this->routeUser->userId());
        }

        // Map the results into a Zend Paginator, lazely converting them to LPA instances as we go...
        $lpaCollection = $this->lpaCollection;

        $callback = new PaginatorCallback(
            function ($offset, $itemCountPerPage) use ($lpaCollection, $filter) {
                // getItems callback
                $options = [
                    'sort' => [
                        'updatedAt' => -1
                    ],
                    'skip' => $offset,
                    'limit' => $itemCountPerPage
                ];

                $cursor = $lpaCollection->find($filter, $options);
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

        return new Collection($callback, $this->routeUser->userId());
    }

    /**
     * @param $id
     * @return ApiProblem|bool
     */
    public function delete($id)
    {
        $this->checkAccess();

        $filter = [
            '_id' => (int) $id,
            'user' => $this->routeUser->userId(),
        ];

        $result = $this->lpaCollection->findOne($filter, ['projection' => ['_id' => true]]);

        if (is_null($result)) {
            return new ApiProblem(404, 'Document not found');
        }

        //  We don't want to remove the document entirely as we need to make sure the same ID isn't reassigned.
        //  So we just strip the document down to '_id' and 'updatedAt'.
        $result['updatedAt'] = new UTCDateTime();

        $this->lpaCollection->replaceOne($filter, $result);

        return true;
    }

    /**
     * @return bool
     */
    public function deleteAll()
    {
        $this->checkAccess();

        $query = ['user' => $this->routeUser->userId()];

        $lpas = $this->lpaCollection->find($query, ['_id' => true]);

        foreach ($lpas as $lpa) {
            $this->delete($lpa['_id']);
        }

        return true;
    }
}
