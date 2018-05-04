<?php
namespace Application\Model\Service\DataAccess\Mongo;

use MongoDB\BSON\UTCDateTime as MongoDate;
use MongoDB\Collection as MongoCollection;

use Application\Model\Service\DataAccess\LogDataSourceInterface;

class LogCollection implements LogDataSourceInterface
{

    protected $collection;

    public function __construct(MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Add a document to the log collection.
     *
     * @param array $details
     * @return bool
     */
    public function addLog( Array $details ){

        // Map DateTimes to MongoDates
        $details = array_map( function($v) {
            return ( $v instanceof \DateTime ) ? new MongoDate( $v ) : $v;
        }, $details);

        $result = $this->collection->insertOne( $details );

        return ( $result->getInsertedCount() == 1 );

    }

    /**
     * Retrieve a log document based on the identity hash stored against it
     *
     * @param string $identityHash
     * @return array
     */
    public function getLogByIdentityHash(string $identityHash)
    {
        $data = $this->collection->findOne(['identity_hash' => $identityHash]);

        if (!is_array($data)) {
            return null;
        }

        return $data;
    }
} // class
