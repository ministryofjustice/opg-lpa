<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use Application\Model\DataAccess\Repository\Stats\StatsRepositoryInterface;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\ReadPreference;

class ApiStatsLpasCollection implements StatsRepositoryInterface
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
     * Insert a new set of stats into the cache.
     *
     * @param array $stats
     * @return bool
     */
    public function insert(array $stats) : bool
    {
        $result = $this->collection->insertOne($stats);

        return ($result->getInsertedCount() == 1);
    }

    /**
     * Returns the current set of cached stats.
     *
     * @return array
     */
    public function getStats() : ?array
    {
        // Return all the cached data. Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        return $this->collection->findOne([], $readPreference);
    }

    /**
     * Delete all previously cached stats.
     */
    public function delete() : bool
    {
        $result = $this->collection->deleteMany([]);

        return $result->isAcknowledged();
    }
}
