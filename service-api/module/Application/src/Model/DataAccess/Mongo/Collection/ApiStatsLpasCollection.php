<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\ReadPreference;

class ApiStatsLpasCollection
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
     * @param $stats
     */
    public function insert($stats)
    {
        $this->collection->insertOne($stats);
    }

    /**
     * @return array|null|object
     */
    public function getStats()
    {
        // Return all the cached data.// Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        return $this->collection->findOne([], $readPreference);
    }

    /**
     * Empty the collection
     */
    public function delete()
    {
        $this->collection->deleteMany([]);
    }
}
