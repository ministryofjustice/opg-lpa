<?php
namespace DynamoQueue;

abstract class AbstractJob {

    const STATE_WAITING = 'waiting';
    const STATE_PROCESSING = 'processing';
    const STATE_DONE = 'done';
    const STATE_ERROR = 'error';

}
