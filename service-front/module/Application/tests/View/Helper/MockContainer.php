<?php

namespace ApplicationTest\View\Helper;

use Zend\Session\Container;

/**
 * Class MockContainer
 *
 * Extend Zend\Session\Container to pass type checking, but store and retrieve the values from an array
 */
class MockContainer extends Container
{
    protected $protectedProperties = [];

    private $values = [];

    public function &offsetGet($key)
    {
        return $this->values[$key];
    }

    public function offsetSet($key, $value) : void
    {
        $this->values[$key] = $value;
    }
}
