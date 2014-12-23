<?php
namespace Opg\Lpa\Pdf\Config;

use ArrayAccess;
use Countable;

class Config implements Countable, ArrayAccess {
    
    static $instance = null;

    private $container = null;

    private function __construct( ) {
        if($this->container === null) {
            $this->container = include('local.php');
        }
    }
    
    static public function getInstance( )
    {
        if(self::$instance === null) {
            self::$instance = new self( );
        }
        
        return self::$instance;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    public function count(){
        return count($this->container);
    }

} // class
