<?php
namespace Opg\Lpa\Pdf\Config;

use ArrayAccess;
use Countable;

class Config implements Countable, ArrayAccess {

    private $container = array();

    public function __construct( array $data = array() ) {
        $this->container = $data;
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
