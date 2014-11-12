<?php
namespace Opg\Lpa\DataModel\Lpa;

interface AccessorInterface {

    /**
     * Returns the value for the requested property.
     *
     * @param string $property The property name.
     * @return mixed
     */
    public function get( $property );

    /**
     * Alias of get(), allowing access to properties via the $lap->{property} syntax.
     *
     * @param string $property The property name.
     * @return mixed
     *
     */
    public function __get( $property );

    /**
     * Sets a property's value.
     * Optionally (but ideally) the value can be validated before it's set.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set the property to.
     * @param bool $validate Whether or not to validate the value before setting it.
     * @return mixed
     */
    public function set( $property, $value, $validate = true );

} // interface
