<?php
namespace DynamoQueueTests\Processors;

use DynamoQueue\Worker\ProcessorInterface;

/**
 * Processor used for testing. Simply echos out the job's id & message.
 *
 * Class EchoMessage
 * @package DynamoQueueTests\Processors
 */
class EchoMessage implements ProcessorInterface {

    public function perform( $jobId, $message ){

        echo "{$jobId} - {$message}\n";

    }

}
