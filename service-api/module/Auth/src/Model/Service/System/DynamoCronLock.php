<?php
namespace Auth\Model\Service\System;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

use Opg\Lpa\Logger\LoggerTrait;

class DynamoCronLock
{
    use LoggerTrait;

    /**
     * The AWS client
     *
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    private $client;

    /**
     * The name of the table holding the key/value store
     *
     * @var string
     */
    private $tableName;

    /**
     * The namespace to prefix keys with.
     *
     * @var string
     */
    private $keyPrefix;

    //---

    public function __construct(DynamoDbClient $client, string $tableName, string $keyPrefix){

        $this->client = $client;

        $this->tableName = $tableName;

        $this->keyPrefix = $keyPrefix;

    }

    public function getLock( $name, $allowedSecondsSinceLastRun ){

        // Current time in milliseconds
        $time = round(microtime(true) * 1000);

        // If the existing lock is older than this time, we can take the lock
        $takeLockIfOlderThan = $time - ( $allowedSecondsSinceLastRun * 1000 );

        try {

            $this->client->updateItem([
                'TableName' => $this->tableName,
                'Key'       => [ 'id' => [ 'S' => "{$this->keyPrefix}/{$name}" ] ],
                'ExpressionAttributeNames' => [
                    '#updated' => 'updated',
                ],
                'ExpressionAttributeValues' => [
                    ':updated' => [ 'N' => (string)$time ],
                    ':diff' => [ 'N' => (string)$takeLockIfOlderThan ],
                ],
                // If the lock is old, or the row doesn't exist...
                'ConditionExpression' => '#updated < :diff or attribute_not_exists(#updated)',
                'UpdateExpression' => 'SET #updated=:updated',
                'ReturnValues' => 'NONE',
                'ReturnConsumedCapacity' => 'NONE'
            ]);

            // No exception means we got the lock.
            // Otherwise a ConditionalCheckFailedException is thrown.

            return true;

        } catch( DynamoDbException $e ){

            // We expect a ConditionalCheckFailedException
            // Anything else is a 'real' exception.
            if( $e->getAwsErrorCode() !== 'ConditionalCheckFailedException' ){

                // Log the exception...
                $this->getLogger()->alert(
                    'Unexpected exception thrown whilst trying to secure a Dynamo Cron Lock',
                    [ 'exception' => $e->getMessage() ]
                );

            }

        } // try

        return false;

    } // function

} // class
