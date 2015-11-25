<?php
namespace DynamoQueue\Worker\Handler;

interface HandlerInterface {

    public function has( $name );

    public function get( $name );

}
