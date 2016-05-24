<?php
namespace DynamoQueue\Worker;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

use DynamoQueue\AbstractClient;
use DynamoQueue\Worker\Handler\HandlerInterface as ProcessHandlerInterface;
use DynamoQueue\Worker\Exception\UnknownProcessorException;

class Client extends AbstractClient {

    /**
     * The last run partition that was checked.
     *
     * @var null|int
     */
    private static $lastPartition = null;

    //--------------------

    public function __construct( DynamoDbClient $client, array $config = [] ){

        $config = $config + [
            'query_limit' => 4,
        ];

        parent::__construct( $client, $config );

    } // function


    /**
     * Returns the run partition to use for the current query.
     *
     * @return int
     */
    protected function getRunPartition(){

        // If we only have 1 partition, this is always 1.
        if( $this->runPartitions == 1 ){
            return 1;
        }

        //---

        // The first time this is called, start at a random partition.
        if( !is_int(self::$lastPartition) ){
            self::$lastPartition = rand( 1, $this->runPartitions );
            return self::$lastPartition;
        }

        //---

        // Move to the next partition...
        self::$lastPartition++;

        if( self::$lastPartition > $this->runPartitions ){
            self::$lastPartition = 1;
        }

        return self::$lastPartition;

    }


    public function getJob( ProcessHandlerInterface $handler ){

        try {

            $result = null;

            // Rotates through each run partition looking for a job.
            for( $i = 1; $i <= $this->runPartitions; $i++ ){

                /*
                 * Note - $i here does not relate to the run partition that's currently being checked.
                 *        Is used simply as a counter over the number of run partition that are being used.
                 */

                $result = $this->queryWaitingJobs();

                // As soon as we find a job, break out of this loop...
                if( count($result['Items']) !== 0 ){
                    break;
                }

                $result = null;

            }

            //----

            // If nothing was returned, stop here.
            if( is_null($result) ){
                return null;
            }

            //-------------------------------------------------------
            // Loop through the found jobs and attempt to acquire one

            foreach( $result['Items'] as $item ){


                $id = $item['id']['S'];
                $processor = $item['processor']['S'];

                // Ensure that we have a processor to handle the job.
                // This is done early so that we never accept a job we can't handle.
                if( !$handler->has( $processor ) ){

                    // It shouldn't actually ever happen though, so it's still an exception.
                    throw new UnknownProcessorException( "A returned job required unknown processor '{$processor}'." );

                }

                try {

                    $result = $this->client->updateItem([
                        'TableName' => $this->config['table_name'],
                        'Key'       => [ 'id' => [ 'S' => (string)$id ] ],
                        'ExpressionAttributeNames' => [
                            '#state' => 'state',
                            '#updated' => 'updated',
                            '#run_partition' => 'run_partition',
                        ],
                        'ExpressionAttributeValues' => [
                            ':waiting' => [ 'S' => Job::STATE_WAITING ],
                            ':processing' => [ 'S' => Job::STATE_PROCESSING ],
                            ':updated' => [ 'N' => (string)round(microtime(true) * 1000) ],
                        ],
                        'ConditionExpression' => '#state = :waiting',
                        'UpdateExpression' => 'SET #state=:processing, #updated=:updated REMOVE #run_partition',
                        'ReturnValues' => 'ALL_NEW',
                        'ReturnConsumedCapacity' => 'TOTAL'
                    ]);

                    //---

                    // If get here without a ConditionalCheckFailedException, then we have acquired a job.

                    // This should never happen, but as a fail-safe, ensure we have some data.
                    if( !isset($result['Attributes']) ){
                        return null;
                    }

                    $job = new Job(
                        $result['Attributes']['id']['S'],
                        $result['Attributes']['processor']['S'],
                        $result['Attributes']['message']['B'],
                        $result['Attributes']['updated']['N']
                    );

                    return $job;

                } catch( DynamoDbException $e ){

                    // A ConditionalCheckFailedException is expected if another worker took the job first.
                    // We just continue into the next foreach loop.

                    if( $e->getAwsErrorCode() !== 'ConditionalCheckFailedException' ){
                        // otherwise re-throw the exception.
                        throw $e;
                    }

                }

            } // foreach

        } catch( DynamoDbException $e ){

            // For now, just pass the exception on.
            throw $e;

        }

        // If we reach here, we could not acquire a job on this run.
        return null;

    } // function

    //---------------------------------------------------------------------------------------

    private function queryWaitingJobs(){

        /*
         * All jobs in the 'partition-order-index' are "waiting", therefore we don't need to query on 'state'.
         */

        return $this->client->query([
            'IndexName'         => 'partition-order-index',
            'TableName'         => $this->config['table_name'],
            'Limit'             => $this->config['query_limit'],
            'ProjectionExpression'   => 'id, processor',
            'ConsistentRead'    => false,
            'ExpressionAttributeNames' => [
                '#run_partition' => 'run_partition',
                '#run_after' => 'run_after',
            ],
            'ExpressionAttributeValues' => [
                ':run_partition' => [ 'N' => (string)$this->getRunPartition() ],
                ':run_after' => [ 'N' => (string)round(microtime(true) * 1000) ] /* Don't return job scheduled for the future */
            ],
            'KeyConditionExpression' => '#run_partition = :run_partition AND #run_after < :run_after',
            'ScanIndexForward' => true, // Ensure they are run in the order they were added.
            'ReturnConsumedCapacity' => 'TOTAL',
        ]);

    } // function

    //---------------------------------------------------------------------------------------

    /**
     * Updates the passed job with the passed state.
     *
     * Note: we can only change a job's state if its current state is 'processing'.
     *
     * @param Job $job
     * @param $state
     */
    public function setJobState( Job $job, $state ){

        #TODO - Restrict what states it can be set to. E.g. it shouldn't be set to 'waiting' again.

        $expressionAttributeNames = [
            '#state' => 'state',
            '#updated' => 'updated',
        ];

        //---

        $expressionAttributeValues = [
            ':newState' => [ 'S' => $state ],
            ':processing' => [ 'S' => Job::STATE_PROCESSING ],
            ':updated' => [ 'N' => (string)round(microtime(true) * 1000) ],
        ];

        //---

        $updateExpression = 'SET #state=:newState, #updated=:updated';

        //---

        $this->client->updateItem([
            'TableName' => $this->config['table_name'],
            'Key'       => [ 'id' => [ 'S' => (string)$job->id() ] ],
            'ExpressionAttributeNames' => $expressionAttributeNames,
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'UpdateExpression' => $updateExpression,
            'ConditionExpression' => '#state = :processing', // We can only change jobs current 'processing'.
            'ReturnConsumedCapacity' => 'TOTAL'
        ]);

    } // function

} // class
