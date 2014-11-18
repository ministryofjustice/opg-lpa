<?php
namespace Opg\Lpa\DataModel\Lpa;

use DateTime, InvalidArgumentException, JsonSerializable;

use Respect\Validation\Validatable;
use Respect\Validation\Exceptions;

use Opg\Lpa\DataModel\Validator\ValidatorException;
use Opg\Lpa\DataModel\Validator\ValidatorResponse;

/**
 * This class is extended by all entities that make up an LPA, including the LPA object itself.
 * It manages all key interactions with the data - setting, getting, validating, exporting - in a consistent
 * and "propagatable" way.
 *
 * Calls to validate() and toArray() - and all methods that use toArray() - propagate down to all values
 * in $this instance that also extend AbstractData.
 *
 * e.g. Lpa -> Document -> Donor -> Name
 *
 * Class AbstractData
 * @package Opg\Lpa\DataModel\Lpa
 */
abstract class AbstractData implements AccessorInterface, ValidatableInterface, JsonSerializable {

    /**
     * @var array Array of Validators (or a function reference that return a Validator)
     */
    protected $validators = array();

    /**
     * @var array Array of mappers
     */
    protected $typeMap = array();

    /**
     * Builds and populates $this chunk of the LPA.
     *
     * If $data is:
     *  - null: Nothing is populated.
     *  - string: We attempt to JSON decode the string and populate the object.
     *  - string: We populate the object from the array.
     *
     * @param null|string|array $data
     */
    public function __construct( $data = null ){

        // If it's a string, assume it's JSON...
        if( is_string( $data ) ){
            $data = json_decode( $data, true );
        }

        // If it's (now) an array...
        if( is_array($data) ){

            // Foreach each passed property...
            foreach( $data as $k => $v ){

                $this->set( $k, $v, false );

            } // foreach

        } // if

    } // function

    //--------------------------------------
    // Getter

    /**
     * Returns the value for the passed property.
     *
     * @param string $property
     * @return mixed
     * @throws InvalidArgumentException If the property does not exist.
     */
    public function __get( $property ){
        return $this->get( $property );
    }

    /**
     * Returns the value for the passed property.
     *
     * @param string $property
     * @return mixed
     * @throws InvalidArgumentException If the property does not exist.
     */
    public function get( $property ){

        if( !property_exists( $this, $property ) ){
            throw new InvalidArgumentException("$property is not a valid property");
        }

        return $this->{$property};

    } // function

    //--------------------------------------
    // Setter

    /**
     * Sets a property's value, after validating it.
     *
     * @param string $property The property name to set.
     * @return AbstractData Returns $this to allow chaining.
     * @throws InvalidArgumentException If the property name is invalid.
     * @throws ValidatorException If the property value does not validate.
     */
    public function __set( $property, $value ){
        return $this->set( $property, $value, true );
    }

    /**
     * Sets a property's value.
     *
     * @param string $property The property name to set.
     * @param bool $validate Should the value be validated before being set.
     * @return AbstractData Returns $this to allow chaining.
     * @throws InvalidArgumentException If the property name is invalid.
     * @throws ValidatorException If the property value does not validate.
     */
    public function set( $property, $value, $validate = true ){

        if( !property_exists( $this, $property ) ){
            throw new InvalidArgumentException("$property is not a valid property");
        }

        //---

        // Check if this $property should by type mapped...

        if( isset($this->typeMap[$property]) ){
            $value = $this->typeMap[$property]( $value );
        }

        //---

        // Stored so we can restore it should the new value not validate.
        $originalValue = $this->{$property};

        $this->{$property} = $value;

        if( $validate && isset($this->validators[$property]) ) {

            $response = $this->validate($property);

            if ($response->hasErrors()) {
                $this->{$property} = $originalValue;
                throw (new ValidatorException("Unable to set invalid value for {$property}."))->setValidatorResponse($response);
            }

        }

        return $this;

    } // function

    //--------------------------------------
    // Validation

    /**
     * Validates one or more values.
     *
     * If:
     *  $propertiesToCheck == null: All defined validators are applied.
     *  $propertiesToCheck == string: The passed property name is validated.
     *  $propertiesToCheck == array: All passed property names are applied.
     *
     * @param null $property
     * @return ValidatorResponse
     * @throws InvalidArgumentException
     */
    public function validate( $propertiesToCheck = null ){

        $response = new ValidatorResponse();

        //---

        // If a property was passed, create an array containing only it.
        // Otherwise include all properties for which there is a validator.
        if( isset($propertiesToCheck) && is_array($propertiesToCheck) ){

            $properties = $propertiesToCheck;

        } elseif( isset($propertiesToCheck) && is_string($propertiesToCheck) ){

            $properties = $propertiesToCheck = [ $propertiesToCheck ];

        } else {

            $properties = array_keys($this->validators);

        }

        //--------------------------------------------
        // Run validators for each property.

        // For each property we're going to validate...
        foreach( $properties as $name ) {

            // false here prevents the value being formatted.
            $value = $this->get( $name, false );

            // Retrieve the relevant validator instance.
            $validator = $this->getValidator( $name );

            try {

                // Validate the value. Exceptions\AbstractNestedException is thrown on failure.
                $validator->assert( $value );

            } catch( Exceptions\AbstractNestedException $e) {

                // If we're there the value failed one or more validation rules.

                $response[$name] = array();

                //----------------------------------------
                // Store the value in the response.
                // Changes depending on the value type.

                if( is_object($value) ) {

                    $response[$name]['value'] = get_class($this);

                    if (method_exists($value, '__toString')) {

                        $response[$name]['value'] = $response[$name]['value'] . ' / ' . (string)$value;

                    } elseif ($value instanceof DateTime) {

                        $response[$name]['value'] = $response[$name]['value'] . ' / ' . $value->format(DateTime::ISO8601);

                    }

                } elseif( is_array($value) ){

                    $response[$name]['value'] = implode(', ', array_map(function($v){
                        return get_class($v);
                    }, $value) );

                } else {
                    $response[$name]['value'] = $value;
                }

                //-------

                $response[$name]['messages'] = array();

                // Add each message. There should be one message per rule the value failed.
                foreach( $e->getIterator() as $exception ){
                    $response[$name]['messages'][] = $exception->getMainMessage();
                }

            } // catch

        } // foreach

        //---------------------------------------------------------------
        // Propagate the validation request down to property values that
        // implement ValidatableInterface.

        // Gets a list of all properties this class has...
        $reflectedProperties = (new \ReflectionClass( $this ))->getProperties();

        foreach( $reflectedProperties as $property ){

            $name = $property->getName();

            // If this property is currently being validated...
            if( is_null($propertiesToCheck) || in_array( $name, $properties ) ) {

                // And it implements ValidatableInterface...
                if ($this->{$name} instanceof ValidatableInterface) {

                    // Call validate on this property...
                    $result = $this->{$name}->validate();

                    if ($result->hasErrors()) {
                        if (!isset($response[$name])) {
                            $response[$name] = array();
                        }
                        $response[$name]['errors'] = $result;
                    } // if

                } // if

            } // if

        } // foreach

        //---

        return $response;

    } // function

    //-------------------

    /**
     * Returns $this as an array, propagating to all properties that implement AccessorInterface.
     *
     * @return array
     */
    public function toArray(){

        $values = get_object_vars( $this );

        // We shouldn't include these...
        unset( $values['typeMap'] );
        unset( $values['validators'] );

        // Recursively convert all items to an array...
        array_walk_recursive( $values, function( &$item, $key ){
            if( $item instanceof AccessorInterface ){
                $item = $item->toArray();
            } elseif ( $item instanceof \DateTime ) {
                $item = $item->format( \DateTime::ISO8601 );
            }
        });

        return $values;

    } // function

    /**
     * Return the array to use whenever json_encode() is called on this instance.
     *
     * @return array
     */
    public function jsonSerialize(){
        return $this->toArray();
    }

    /**
     * Returns $this as JSON, propagating to all properties that implement AccessorInterface.
     *
     * @return string
     */
    public function toJson(){
        return json_encode( $this, JSON_PRETTY_PRINT );
    }

    /**
     * Returns a flat (not multidimensional) array representing $this.
     *
     * This is done by generating array keys based on the object hierarchy.
     *
     * For example:
     *  Lpa -> Document -> Donor -> Name -> Title
     *  will map to
     *  array[lpa-document-donor-name-title]
     *
     * @return array
     */
    public function flatten(){
        return $this->flattenArray( $this->toArray() );
    }

    //-------------------

    /**
     * Method for recursively walking over our array, flattening it.
     * To trigger it, call $this->flatten()
     *
     */
    private function flattenArray($array, $prefix = 'lpa-') {
        $result = array();
        foreach($array as $key=>$value) {
            if(is_array($value)) {
                $result = $result + $this->flattenArray($value, $prefix . $key . '-');
            }
            else {
                $result[$prefix.$key] = $value;
            }
        }
        return $result;
    }

    //-------------------

    /**
     * Lazy loads the validator for the passed property name.
     *
     * @param string $property The property name.
     * @return \Respect\Validation\Validator Instance of a validator.
     * @throws InvalidArgumentException If there is no validator for the requested property.
     */
    protected function getValidator( $property ){

        if( !isset($this->validators[$property]) ){
            throw new InvalidArgumentException("No validator for $property found");
        }

        $validator = $this->validators[$property];

        if( is_object($validator) && ($validator instanceof \Closure) ) {
            $validator = $validator();
        }

        if( !($validator instanceof Validatable) ){
            throw new InvalidArgumentException("No validator for $property found");
        }

        return $validator;

    } // function

} // abstract class
