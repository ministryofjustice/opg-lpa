<?php
namespace DynamoQueue\Queue\Job;

use DynamoQueue\AbstractJob;

class Job extends AbstractJob {

    /**
     * @var string The job's unique identifier.
     */
    private $id;

    /**
     * @var string The name of the processor on the worker that should pick this job up.
     */
    private $processor;

    /**
     * @var string The job's message body. This is stored a in binary value, but stored in PHP as a string.
     */
    private $message;

    /**
     * @var int The time at (or after) which the job should be run.
     */
    private $time;


    public function __construct( $id, $processor, $message, $time ){
        $this->id = $id;
        $this->processor = $processor;
        $this->message = $message;
        $this->time = $time;
    }

    public function id(){
        return $this->id;
    }

    public function processor(){
        return $this->processor;
    }

    public function message(){
        return $this->message;
    }

    public function time(){
        return $this->time;
    }

}
