<?php
namespace DynamoQueue\Queue\Exception;

/**
 * Exception denotes that data passed to be used as a
 * job's 'message' was not in a supported data type.
 *
 * Class InvalidMessageType
 * @package DynamoQueue\Queue\Exception
 */
class InvalidMessageType extends Exception {}
