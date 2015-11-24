<?php
namespace DynamoQueue\Queue;

use DynamoQueue\AbstractClient;

use DynamoQueue\Queue\Job\Job;
use DynamoQueue\Queue\Exception\InvalidMessageType;
use DynamoQueue\Queue\Exception\DuplicateIdentifierException;

use Aws\DynamoDb\Exception\DynamoDbException;

class Client extends AbstractClient {

    /**
     * @param string $processor The name of the processor on the worker that should pick this job up.
     * @param string $message The job's message body. This is stored as in binary value, but stored in PHP as a string.
     * @param int $time The unix time at (or after) the job should be run, *in milliseconds*.
     * @param null|string $id The unique id to use for the job. Passing NULL will mean this is automatically generated.
     * @return string The job's id.
     * @throws DuplicateIdentifierException If a job with the same id is already in the queue.
     * @throws DynamoDbException If there was a connection issue with DynamoDB.
     * @throws InvalidMessageType If an invalid (non-scalar) value is passed as the message.
     */
    public function enqueue( $processor, $message, $id = null, $time = null ){

        if( !is_string($message) ){
            throw new InvalidMessageType( "A job's message must be a string value. We do not support ".gettype($message)."." );
        }

        //---

        if( !is_numeric( $time ) ){
            $time = (int)round(microtime(true) * 1000);
        }

        if( !is_string($id) ){
            $id = $this->generateJobId();
        }

        //---

        $job = new Job( $id, $processor, $message, $time );

        $this->addJob( $job );

        return $job->id();

    }

    /**
     * Checks the status of the passed job id in the queue.
     *
     * @param $id
     * @return string
     * @throws DynamoDbException
     */
    public function checkStatus( $id ){

        $result = $this->client->getItem([
            'TableName'      => $this->config['table_name'],
            'Key'            => ['id' => ['S' => $id]],
            'AttributesToGet'=> [ 'state' ],
            'ConsistentRead' => true,
        ]);

        // If the Item exists, this will return the state.
        // Otherwise it returns NULL.
        $value = $result->getPath( 'Item/state/S' );

        return (is_string($value)) ? $value : 'not-in-queue';

    }


    /**
     * Generates a random value to be used as the job's unique identifier.
     *
     * @return string
     */
    public static function generateJobId()
    {
        return md5(uniqid('', true));
    }

    /**
     * Randomly selects a run partition to use for the current insert.
     *
     * @return int
     */
    protected function getRunPartition(){

        if ( !is_int($this->runPartitions) || $this->runPartitions <= 1 ){
            return 1;
        }

        return rand( 1, $this->runPartitions );

    }

    //-------------------------------------------------

    /**
     * Adds a job onto the queue.
     *
     * A DuplicateIdentifierException exception will be thrown if a job with the same id already exists, unless that
     * job has a state of 'done'.
     *
     * ( Generally always CapacityUnits = 2 )
     *
     * @param Job $job
     * @return bool
     * @throws DynamoDbException
     * @throws DuplicateIdentifierException
     * @throws \Exception
     */
    public function addJob( Job $job ){

        $id = $job->id();

        $time = round(microtime(true) * 1000);

        $request = [
            'TableName' => $this->config['table_name'],
            'ExpressionAttributeNames' => [
                '#state' => 'state'
            ],
            'ExpressionAttributeValues' => [
                ':done' => [ 'S' => 'done' ]
            ],
            'ConditionExpression' => 'attribute_not_exists(id) OR #state = :done',
            'Item' => [
                'id' => [ 'S' => (string)$id ],
                'state' => [ 'S' => 'waiting' ],
                'processor' => [ 'S' => (string)$job->processor() ],
                'added' => [ 'N' => (string)$time ],
                'updated' => [ 'N' => (string)$time ],
                'message' => [ 'B' => $job->message() ],
                'run_after' => [ 'N' => (string)round( $job->time() ) ],
                'run_partition' => [ 'N' => (string)$this->getRunPartition() ],
            ],
        ];

        try {

            $this->client->putItem( $request );

        } catch( DynamoDbException $e ){

            if( $e->getAwsErrorCode() === 'ConditionalCheckFailedException' ){

                // This occurs if the ConditionExpression above fails. i.e. The id already exists.
                throw new DuplicateIdentifierException( "A job with ID {$id} is already in the queue" );

            } else {

                // Else re-throw the original exception.
                throw $e;

            }

        }

        return true;

    } // function

} // class
