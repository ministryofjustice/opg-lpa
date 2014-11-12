<?php
namespace Opg\Lpa\DataModel\Lpa;

use InvalidArgumentException, JsonSerializable;

use Respect\Validation\Validatable;
use Respect\Validation\Exceptions;

use Opg\Lpa\DataModel\Validator\ValidatorException;
use Opg\Lpa\DataModel\Validator\ValidatorResponse;


abstract class AbstractData implements AccessorInterface, ValidatableInterface, JsonSerializable {

    protected $validators = array();

    protected $typeMap = array();

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

    public function __get( $property ){
        return $this->get( $property );
    }

    public function get( $property ){

        if( !property_exists( $this, $property ) ){
            throw new InvalidArgumentException("$property is not a valid property");
        }

        return $this->{$property};

    } // function

    //--------------------------------------
    // Setter

    public function __set( $property, $value ){
        return $this->set( $property, $value, true );
    }

    public function set( $property, $value, $validate = true ){

        if( !property_exists( $this, $property ) ){
            throw new InvalidArgumentException("$property is not a valid property");
        }

        //---

        // Check if this $property should by type mapped...

        if( $this->typeMap[$property] ){
            $value = $this->typeMap[$property]( $value );
        }

        //---

        $originalValue = $this->{$property};

        $this->{$property} = $value;

        if( $validate ) {

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
     * $property == null: All defined validators are applied.
     * $property == string: The passed property name is validated.
     * $property == array: All passed property names are applied.
     *
     * @param null $property
     * @return ValidatorResponse
     * @throws InvalidArgumentException
     */
    public function validate( $propertiesToCheck = null ){

        $errors = new ValidatorResponse();

        //---

        // If a property was passed, create an array containing only it.
        // Otherwise include all $properties for which there is a validator.
        if( isset($propertiesToCheck) && is_array($propertiesToCheck) ){

            $properties = $propertiesToCheck;

        } elseif( isset($propertiesToCheck) && is_string($propertiesToCheck) ){

            $properties = $propertiesToCheck = [ $propertiesToCheck ];

        } else {

            $properties = array_keys($this->validators);

        }

        //--------------------------------------------
        // Run validators defined in $this class.

        // For each property we're going to validate...
        foreach( $properties as $name ) {

            // false prevents the value being formatted.
            $value = $this->get( $name, false );

            $validator = $this->getValidator( $name );

            try {

                $validator->assert( $value );

            } catch( Exceptions\AbstractNestedException $e) {

                $errors[$name] = array();

                $errors[$name]['value'] = ( is_object($value) ) ? (string)$value : $value;
                $errors[$name]['messages'] = array();

                foreach( $e->getIterator() as $exception ){
                    $errors[$name]['messages'][] = $exception->getMainMessage();
                }

            } // catch

        } // foreach

        //--------------------------------------------
        // Run validators defined in property classes

        $reflectedProperties = (new \ReflectionClass( $this ))->getProperties();

        foreach( $reflectedProperties as $property ){

            $name = $property->getName();

            if( is_null($propertiesToCheck) || in_array( $name, $properties ) )

            if( $this->{$name} instanceof ValidatableInterface ){

                // Call validate on this property...
                $response = $this->{$name}->validate();

                if( $response->hasErrors() ){
                    if( !isset($errors[$name]) ){ $errors[$name] = array(); }
                    $errors[$name]['errors'] = $response;
                } // if

            } // if

        } // foreach

        //---

        return $errors;

    } // function

    //-------------------

    public function toArray(){

        $values =  get_object_vars( $this );

        unset( $values['typeMap'] );
        unset( $values['validators'] );

        array_walk_recursive( $values, function( &$item, $key ){
            if( $item instanceof AccessorInterface ){
                $item = $item->toArray();
            } elseif ( $item instanceof \DateTime ) {
                $item = $item->format( \DateTime::ISO8601 );
            }
        });

        return $values;

    } // function

    public function jsonSerialize(){
        return $this->toArray();
    } // function

    public function toJson(){
        return json_encode( $this, JSON_PRETTY_PRINT );
    }

    //-------------------

    /**
     * Lazy loads the validator for the passed property name.
     *
     * @param $property
     * @return mixed
     * @throws InvalidArgumentException
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
