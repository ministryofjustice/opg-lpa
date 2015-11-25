<?php
namespace DynamoQueue\Worker;

interface ProcessorInterface {

    public function perform( $jobId, $message );

}
