<?php
namespace DynamoQueue\Worker;

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
     * @var string The job's message body. This is stored as a binary value, but stored in PHP as a string.
     */
    private $message;

    /**
     * @var number The priority of this job. By default lower numbers are processed before larger numbers.
     */
    private $startTime;


    public function __construct( $id, $processor, $message, $startTime ){
        $this->id = $id;
        $this->processor = $processor;
        $this->message = $message;
        $this->startTime = (int)$startTime;
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

    public function started(){
        return $this->startTime;
    }

}
