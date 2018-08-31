<?php

namespace Opg\Lpa\DataModel;

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
     * Alias of get(), allowing access to properties via the $lap->{property} syntax.
     *
     * @param string $property The property name.
     * @return mixed
     *
     */
    public function __get($property);

    /**
     * Sets a property's value.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set the property to.
     * @return mixed
     */
    public function set($property, $value);

    /**
     * Sets a property's value, after validating it.
     *
     * @param string $property The property name to set.
     * @param mixed $value The value to set property to.
     * @return AbstractData Returns $this to allow chaining.
     * @throws \InvalidArgumentException If the property name is invalid.
     */
    public function __set($property, $value);

    /**
     * Returns an array representation of $this instance.
     *
     * @param bool $retainDateTimeInstances
     * @return array
     */
    public function toArray(bool $retainDateTimeInstances = false);

    /**
     * Returns an JSON representation of $this instance.
     *
     * @return string
     */
    public function toJson();

    /**
     * Returns a flat (not multidimensional) array representing $this.
     *
     * This is done by generating array keys based on the object hierarchy.
     *
     * For example:
     *  Lpa -> Document -> Donor -> Name -> Title
     *  will map to
     *  [lpa-document-donor-name-title]
     *
     * @return array
     */
    public function flatten();
}
