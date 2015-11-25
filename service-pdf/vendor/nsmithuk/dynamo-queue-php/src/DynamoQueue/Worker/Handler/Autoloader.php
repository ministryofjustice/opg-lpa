<?php
namespace DynamoQueue\Worker\Handler;

/**
 * This basic Handler assumes that the process name is a class name that can be found via the PHP auto-loader.
 *
 * Class Classmap
 * @package DynamoQueue\Worker\Handler
 */
class Autoloader implements HandlerInterface {

    public function has( $name ){

        return class_exists( $name ) && in_array( 'DynamoQueue\Worker\ProcessorInterface', class_implements($name) );

    }

    public function get( $name ){

        return new $name();

    }

}
