<?php

namespace MakeShared\DataModel;

interface AccessorInterface
{
    /**
     * Returns the value for the requested property.
     *
     * @param string $property The property name.
     * @return mixed
     */
    public function get($property);

    /**
     * Sets a property's value.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set the property to.
     * @return mixed
     */
    public function set($property, $value);

    /**
     * Returns an array representation of $this instance.
     *
     * @param bool $retainDateTimeInstances
     * @return array
     */
    public function toArray(bool $retainDateTimeInstances = false);
}
