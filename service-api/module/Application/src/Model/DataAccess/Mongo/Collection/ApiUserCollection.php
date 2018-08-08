<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use Application\Library\DateTime;
use Application\Model\DataAccess\Mongo\DateCallback;
use Opg\Lpa\DataModel\User\User as UserModel;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection as MongoCollection;
use RuntimeException;

class ApiUserCollection
{
    /**
     * @var MongoCollection
     */
    protected $collection;

    /**
     * @param MongoCollection $collection
     */
    public function __construct(MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @param $id
     * @return array|null|object
     */
    public function getById($id)
    {
        return $this->collection->findOne([
            '_id' => $id
        ]);
    }

    /**
     * @param UserModel $user
     * @return \MongoDB\InsertOneResult
     */
    public function insert(UserModel $user)
    {
        return $this->collection->insertOne($user->toArray(new DateCallback()));
    }

    /**
     * @param UserModel $user
     */
    public function update(UserModel $user)
    {
        $lastUpdated = new UTCDateTime($user->updatedAt);

        // Record the time we updated the user
        $user->updatedAt = new DateTime();

        // updatedAt is included in the query so that data isn't overwritten
        // if the User has changed since this process loaded it.
        $result = $this->collection->updateOne(
            ['_id' => $user->id, 'updatedAt' => $lastUpdated],
            ['$set' => $user->toArray(new DateCallback())],
            ['upsert' => false, 'multiple' => false]
        );

        // Ensure that one (and only one) document was updated.
        // If not, something when wrong.
        if ($result->getModifiedCount() !== 0 && $result->getModifiedCount() !== 1) {
            throw new RuntimeException('Unable to update User. This might be because "updatedAt" has changed.');
        }
    }

    /**
     * @param $id
     * @return \MongoDB\DeleteResult
     */
    public function deleteById($id)
    {
        return $this->collection->deleteOne([
            '_id' => $id
        ]);
    }
}
