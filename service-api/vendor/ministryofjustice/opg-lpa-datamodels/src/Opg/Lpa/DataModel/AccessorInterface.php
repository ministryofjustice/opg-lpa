<?php
namespace Opg\Lpa\DataModel;

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

    /**
     * Sets a property's value, after validating it.
     *
     * @param string $property The property name to set.
     * @return AbstractData Returns $this to allow chaining.
     * @throws \InvalidArgumentException If the property name is invalid.
     * @throws \Opg\Lpa\DataModel\Validator\ValidatorException If the property value does not validate.
     */
    public function __set( $property, $value );

    /**
     * Returns an array representation of $this instance.
     *
     * @return array
     */
    public function toArray();

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

} // interface
