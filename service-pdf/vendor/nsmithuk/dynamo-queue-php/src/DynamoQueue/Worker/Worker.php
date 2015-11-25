<?php
namespace DynamoQueue\Worker;

use Exception;

use Psr\Log\LoggerInterface;

use Aws\DynamoDb\Exception\DynamoDbException;

use DynamoQueue\Worker\Client as Queue;
use DynamoQueue\Worker\Exception\UnknownProcessorException;
use DynamoQueue\Worker\Handler\HandlerInterface as ProcessHandlerInterface;


class Worker {

    /**
     * @var Client The DynamoQueue Worker Client
     */
    protected $queue;

    /**
     * @var ProcessHandlerInterface The job handler.
     */
    protected $handler;

    /**
     * @var LoggerInterface The logger.
     */
    private $logger;

    /**
     * @var int Tracks the number of consecutive DynamoDb Exceptions that have been thrown.
     */
    private $dynamoDbErrors = 0;

    /**
     * @var bool Should the worker continue to run. Setting this to false will terminate the worker.
     */
    private $run = true;

    //---

    public function __construct( Queue $queue, ProcessHandlerInterface $handler, LoggerInterface $logger ){

        $this->queue = $queue;
        $this->handler = $handler;
        $this->logger = $logger;

    }

    /**
     * Stops the worker ASAP (it will finished any started jobs)
     */
    public function stop(){

        $this->logger->notice("Initiating stop");
        $this->run = false;

    }

    /**
     * Starts (and continues to run) the worker.
     *
     * @return bool
     */
    public function run(){

        $this->run = true;

        // This loop will run until the Worker is terminated
        while( $this->run ){

            // Stores the current job that's being processed.
            $job = null;

            try {

                $job = $this->queue->getJob( $this->handler );

                if( !is_null( $job ) ){

                    // If we're here, we have a job to process.

                    try {

                        $processorName = $job->processor();

                        $hasProcessor = $this->handler->has( $processorName );

                        if(!$hasProcessor){
                            throw new UnknownProcessorException("No processor found for '{$processorName}'");
                        }

                        $processor = $this->handler->get( $processorName );

                        //---

                        $this->logger->info("Starting job ".$job->id()." with {$processorName}");

                        // Run the job.
                        // We assume this runs fine unless an exception is thrown.
                        $processor->perform( $job->id(), $job->message() );

                        //---

                        $this->queue->setJobState( $job, $job::STATE_DONE );

                        $this->logger->info("Finished job ".$job->id()." with {$processorName}");

                    } catch( Exception $e ){

                        $this->logger->error( "Unable to process job ".$job->id(), [
                            'id' => $job->id(),
                            'processor'=> $job->processor(),
                            'exception' => $e
                        ] );

                        $this->queue->setJobState( $job, $job::STATE_ERROR );

                    }

                } else {

                    // If no job was returned, there is nothing in the queue.

                    $this->logger->debug("Nothing to do - sleeping");

                    // If no job was returned, sleep a little while before trying again.
                    pcntl_signal_dispatch();
                    if ( !$this->run ){ return true; }

                    sleep(1);

                }

                //---

                // Reset the error count back to 0.
                $this->dynamoDbErrors = 0;

            } catch ( DynamoDbException $e ){

                /*
                 * We have an issue with DynamoDB. This is most likely temporary.
                 * Throttling / exceeding ProvisionedThroughput is the general cause.
                 */

                // If this has happened 10 consecutive times...
                if( $this->dynamoDbErrors >= 10 ){

                    $this->logger->alert( "We've had 10 consecutive DynamoDbExceptions - giving up", [ 'exception'=>$e ] );

                    // Return !okay
                    return false;

                }

                $this->logger->warning( "DynamoDbException thrown. The operation will be retried", [ 'exception'=>$e ] );

                $this->dynamoDbErrors++;

                pcntl_signal_dispatch();
                if ( !$this->run ){ return true; }

                sleep(5);

            } catch( Exception $e ){

                // Something truly strange happened. Exit the loop.
                $this->logger->emergency( "Unknown Exception throw - unable to continue", [ 'exception'=>$e ] );

                // Return !okay
                return false;

            }

            //---

            pcntl_signal_dispatch();

        } // while

        // Return okay
        return true;

    } // function

} // class
