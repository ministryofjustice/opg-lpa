<?php
namespace Opg\Lpa\DataModel;

use DateTime, InvalidArgumentException, JsonSerializable;

use Opg\Lpa\DataModel\Validator\ValidatorResponse;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ConstraintViolationList;

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
abstract class AbstractData implements AccessorInterface, JsonSerializable, Validator\ValidatableInterface {

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

        // If it's a string...
        if( is_string( $data ) ){

            // Assume it's JSON.
            $data = json_decode( $data, true );

            // Throw an exception if it turns out to not be JSON...
            if( is_null($data) ){ throw new InvalidArgumentException('Invalid JSON passed to constructor'); }

        } // if


        // If it's [now] an array...
        if( is_array($data) ){

            $this->populate( $data );

        } elseif( !is_null( $data ) ){

            // else if it's not null (or array) now, it was an invalid data type...
            throw new InvalidArgumentException('Invalid argument passed to constructor');

        }

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
    public function &__get( $property ){
        return $this->get( $property );
    }

    /**
     * Returns the value for the passed property.
     *
     * @param string $property
     * @return mixed
     * @throws InvalidArgumentException If the property does not exist.
     */
    public function &get( $property ){

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
     */
    public function __set( $property, $value ){
        return $this->set( $property, $value );
    }

    /**
     * Sets a property's value.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set the property to.
     * @return mixed
     */
    public function set( $property, $value ){

        if( !property_exists( $this, $property ) ){
            throw new InvalidArgumentException("$property is not a valid property");
        }

        //---

        /**
         * MongoDates should be converted to Datatime.
         * Once we have ext-mongo >= 1.6, we can use $value->toDateTime()
         */
        if( class_exists('\MongoDate') && $value instanceof \MongoDate ){
            // sprintf %06d ensures a full 6 digit value is returns, even if there are prefixing zeros.
            $value = new DateTime( date( 'Y-m-d\TH:i:s', $value->sec ).".".sprintf("%06d", $value->usec)."+0000" );
        }

        //---

        // Map the value (if needed)...
        $value = $this->map( $property, $value );

        //---

        $this->{$property} = $value;

        return $this;

    } // function

    //--------------------------------------
    // Validation

    /**
     * Validates the concrete class which this method is called on.
     *
     * @param $properties Array An array of property names to check. An empty array means all properties.
     * @return ValidatorResponse
     * @throws InvalidArgumentException
     */
    public function validate( Array $properties = array() ){

        $validator = Validation::createValidatorBuilder()
            ->setApiVersion( Validation::API_VERSION_2_5 )
            ->addMethodMapping('loadValidatorMetadata')->getValidator();

        if( !empty($properties) ){

            // Validate the passed properties...

            $violations = new ConstraintViolationList();

            foreach( $properties as $property ){
                $result = $validator->validateProperty( $this, $property );
                $violations->addAll( $result );
            }

        } else {
            // Validate all properties...
            $violations = $validator->validate( $this );
        }

        //---

        $response = new ValidatorResponse();

        // If there no errors, we can return straight away.
        if( count($violations) == 0 ){
            return $response;
        }

        //---

        foreach($violations as $violation){

            $field = $violation->getPropertyPath();

            // If this is the first time we've seen an error for this field...
            if( !isset($response[$field]) ){

                $value = $violation->getInvalidValue();

                // If the value is an object...
                if( is_object($value) ) {

                    if (method_exists($value, '__toString')) {

                        $value = get_class($this) . ' / ' . (string)$value;

                    } elseif ($value instanceof DateTime) {

                        $value = $value->format(DateTime::ISO8601);

                    } else {

                        $value = get_class($this);

                    }

                } elseif( is_array($value) ){

                    $value = implode(', ', array_map(function($v){
                        return get_class($v);
                    }, $value) );

                }

                $response[$field] = [
                    'value' => $value,
                    'messages' => array()
                ];

            } // if

            //---

            // Include the error message
            $response[$field]['messages'][] = $violation->getMessage();

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
    public function toArray( $dateFormat = 'string' ){

        if( $dateFormat == 'mongo' && !class_exists('\MongoDate') ){
            throw new InvalidArgumentException('You not have the PHP Mongo extension installed');
        }

        //---

        $values = get_object_vars( $this );

        foreach( $values as $k=>$v ){

            if ( $v instanceof DateTime ) {

                switch($dateFormat){
                    case 'string':
                        $values[$k] = $v->format( 'Y-m-d\TH:i:s.uO' ); // ISO8601 including microseconds
                        break;
                    case 'mongo':
                        //Convert to MongoDate, including microseconds...
                        $values[$k] = new \MongoDate( $v->getTimestamp(), (int)$v->format('u') );
                        break;
                    default:
                } // switch

            } // if

            // Recursively build this array...
            if( $v instanceof AccessorInterface ) {
                $values[$k] = $v->toArray( $dateFormat );
            }

            // If the value is an array, check if it contains instances of AccessorInterface...
            if( is_array($v) ){
                // If so, map them...
                foreach( $v as $a=>$b ){
                    if( $b instanceof AccessorInterface ) {
                        $values[$k][$a] = $b->toArray( $dateFormat );
                    }
                }
            } // if

        } // foreach

        return $values;

    } // function

    public function getArrayCopy(){
        throw new \Exception( 'Is this used anywhere? If not I am going to remove it.' );
        return $this->toArray();
    }

    /**
     * Returns $this as an array suitable for inserting into MongoDB.
     *
     * @return array
     */
    public function toMongoArray(){
        return $this->toArray( 'mongo' );

        //MongoDate
    }

    /**
     * Return the array to use whenever json_encode() is called on this instance.
     *
     * @return array
     */
    public function jsonSerialize(){
        return $this->toArray( 'string' );
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
    public function flatten($prefix = ''){
        return $this->flattenArray( $this->toArray( 'string' ), $prefix );
    }

    //-------------------

    /**
     * Method for recursively walking over our array, flattening it.
     * To trigger it, call $this->flatten()
     */
    private function flattenArray($array, $prefix) {
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

    /**
     * Recursively walks over a flat array (separated with dashes) and
     * converts it to a multidimensional array.
     *
     * @param $array array Flat array.
     * @return array Multidimensional array
     */
    private function unFlattenArray( $array ){

        $result = array();

        foreach( $array as $key => $value ){

            $keys = explode( '-', $key );

            $position = &$result;

            foreach( $keys as $index ){
                $position = &$position[$index];
            }

            $position = $value;

        }

        return $result;

    } // function

    //-------------------
    // Hydrator methods

    /**
     * Populates the concrete class' properties with the array.
     *
     * @param array $data
     */
    public function populate( Array $data ){

        // Foreach each passed property...
        foreach( $data as $k => $v ){

            // Only include known properties during the import...
            if( property_exists( $this, $k ) && !is_null($v) ){
                $this->set( $k, $v );
            }

        } // foreach

    } // function

    /**
     * Populates the concrete class' properties with the passed flat array.
     *
     * @param array $data
     */
    public function populateWithFlatArray( Array $data ){

        $data = $this->unFlattenArray( $data );

        $this->populate( $data );

    } // function

    //-------------------

    /**
     * Basic mapper. This should be overridden in the concrete class if needed.
     * This is included here to ensure the method is always available
     * and - by default - returns the original value it was passed.
     *
     * @param $property string The property name.
     * @param $value mixed The value we've been passed.
     * @return mixed The potentially updated value.
     */
    protected function map( $property, $value ){
        return $value;
    } // function

} // abstract class
