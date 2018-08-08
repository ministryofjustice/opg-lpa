<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use Application\Model\DataAccess\Mongo\DateCallback;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use MongoDB\BSON\ObjectID as MongoId;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\ReadPreference;

class ApiWhoCollection
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
     * @param WhoAreYou $answer
     * @return \MongoDB\InsertOneResult
     */
    public function insert(WhoAreYou $answer)
    {
        return $this->collection->insertOne($answer->toArray(new DateCallback()));
    }

    /**
     * Return the WhoAreYou values for a specific date range.
     *
     * @param $start
     * @param $end
     * @param $options
     * @return array
     */
    public function getStatsForTimeRange($start, $end, $options)
    {
        // Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        // Convert the timestamps to MongoIds
        $start = str_pad(dechex($start), 8, "0", STR_PAD_LEFT);
        $start = new MongoId($start."0000000000000000");

        $end = str_pad(dechex($end), 8, "0", STR_PAD_LEFT);
        $end = new MongoId($end."0000000000000000");

        $range = [
            '$gte' => $start,
            '$lte' => $end
        ];

        $result = [];

        // For each top level 'who' level...
        foreach ($options as $topLevel => $details) {
            // Get the count for all top level...
            $result[$topLevel] = [
                'count' => $this->collection->count([
                    'who' => $topLevel,
                    '_id' => $range
                ], $readPreference),
            ];
        }

        return $result;
    }
}
