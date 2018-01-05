<?php

namespace Opg\Lpa\DataModel;

use Opg\Lpa\DataModel\Validator\ValidatorResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ConstraintViolationList;
use DateTime;
use InvalidArgumentException;
use JsonSerializable;

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
abstract class AbstractData implements AccessorInterface, JsonSerializable, Validator\ValidatableInterface
{
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
    public function __construct($data = null)
    {
        // If it's a string...
        if (is_string($data)) {
            // Assume it's JSON.
            $data = json_decode($data, true);

            // Throw an exception if it turns out to not be JSON...
            if (is_null($data)) {
                throw new InvalidArgumentException('Invalid JSON passed to constructor');
            }
        }

        // If it's [now] an array...
        if (is_array($data)) {
            $this->populate($data);
        } elseif (!is_null($data)) {
            // else if it's not null (or array) now, it was an invalid data type...
            throw new InvalidArgumentException('Invalid argument passed to constructor');
        }
    }

    /**
     * Determines whether a property with the passed name exists.
     *
     * @param $property
     * @return bool
     */
    public function __isset($property)
    {
        return isset($this->{$property});
    }

    /**
     * Returns the value for the passed property.
     *
     * @param string $property
     * @return mixed
     * @throws InvalidArgumentException If the property does not exist.
     */
    public function &__get($property)
    {
        return $this->get($property);
    }

    /**
     * Returns the value for the passed property.
     *
     * @param string $property
     * @return mixed
     * @throws InvalidArgumentException If the property does not exist.
     */
    public function &get($property)
    {
        if (!property_exists($this, $property)) {
            throw new InvalidArgumentException("$property is not a valid property");
        }

        return $this->{$property};
    }

    /**
     * Sets a property's value, after validating it.
     *
     * @param string $property The property name to set.
     * @param mixed $value The value to set property to.
     * @return AbstractData Returns $this to allow chaining.
     * @throws InvalidArgumentException If the property name is invalid.
     */
    public function __set($property, $value)
    {
        return $this->set($property, $value);
    }

    /**
     * Sets a property's value.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set the property to.
     * @return mixed
     */
    public function set($property, $value)
    {
        if (!property_exists($this, $property)) {
            throw new InvalidArgumentException("$property is not a valid property");
        }

        //  If this value has a toDateTime method then call that here
        if (method_exists($value, 'toDateTime')) {
            $value = $value->toDateTime();
        }

        // Map the value (if needed)...
        $value = $this->map($property, $value);

        $this->{$property} = $value;

        return $this;
    }

    // Validation

    /**
     * Calls validate(), including all validation groups.
     *
     * @return ValidatorResponse
     */
    public function validateAllGroups()
    {
        return $this->validate([], [
            'Default',
            'required-at-api',
            'required-at-pdf'
        ]);
    }

    /**
     * Calls validate(), including all validations needed at the API level.
     *
     * @return ValidatorResponse
     */
    public function validateForApi()
    {
        return $this->validate([], [
            'Default',
            'required-at-api'
        ]);
    }

    /**
     * Validates the concrete class which this method is called on.
     *
     * @param $properties array An array of property names to check. An empty array means all properties.
     * @param $groups array An array of what validator groups to check (if any).
     * @return ValidatorResponse
     * @throws InvalidArgumentException
     */
    public function validate(array $properties = [], array $groups = [])
    {
        $validator = Validation::createValidatorBuilder()->addMethodMapping('loadValidatorMetadata')
                                                         ->getValidator();

        // Marge any other require groups in along with Default.
        $groups = array_unique(array_merge($groups, ['Default']));

        if (!empty($properties)) {
            // Validate the passed properties...
            $violations = new ConstraintViolationList();

            foreach ($properties as $property) {
                $result = $validator->validateProperty($this, $property, $groups);
                $violations->addAll($result);
            }
        } else {
            // Validate all properties...
            $violations = $validator->validate($this, null, $groups);
        }

        $response = new ValidatorResponse();

        // If there no errors, we can return straight away.
        if (count($violations) == 0) {
            return $response;
        }

        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();

            // If this is the first time we've seen an error for this field...
            if (!isset($response[$field])) {
                $value = $violation->getInvalidValue();

                // If the value is an object...
                if (is_object($value)) {
                    if (method_exists($value, '__toString')) {
                        $value = get_class($this) . ' / ' . (string)$value;
                    } elseif ($value instanceof DateTime) {
                        $value = $value->format(DateTime::ISO8601);
                    } else {
                        $value = get_class($this);
                    }
                } elseif (is_array($value)) {
                    $value = implode(', ', array_map(function ($v) {
                        if (is_string($v)) {
                            return $v;
                        }
                        return get_class($v);
                    }, $value));
                }

                $response[$field] = [
                    'value' => $value,
                    'messages' => []
                ];
            }

            // Include the error message
            $response[$field]['messages'][] = $violation->getMessage();
        }

        return $response;
    }

    /**
     * Returns $this as an array, propagating to all properties that implement AccessorInterface.
     *
     * @param callable|null $dateCallback
     * @return array
     */
    public function toArray(callable $dateCallback = null)
    {
        $values = get_object_vars($this);

        foreach ($values as $k => $v) {
            if ($v instanceof DateTime) {
                if (is_callable($dateCallback)) {
                    $values[$k] = call_user_func($dateCallback, $v);
                } else {
                    //  Get the value as a normal datetime string
                    $values[$k] = $v->format('Y-m-d\TH:i:s.uO'); // ISO8601 including microseconds
                }
            }

            // Recursively build this array...
            if ($v instanceof AccessorInterface) {
                $values[$k] = $v->toArray($dateCallback);
            }

            // If the value is an array, check if it contains instances of AccessorInterface...
            if (is_array($v)) {
                // If so, map them...
                foreach ($v as $a => $b) {
                    if ($b instanceof AccessorInterface) {
                        $values[$k][$a] = $b->toArray($dateCallback);
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Return the array to use whenever json_encode() is called on this instance.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Returns $this as JSON, propagating to all properties that implement AccessorInterface.
     *
     * @param bool $pretty
     * @return string
     */
    public function toJson($pretty = true)
    {
        if ($pretty) {
            return json_encode($this, JSON_PRETTY_PRINT);
        } else {
            return json_encode($this);
        }
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
     * @param string $prefix
     * @return array
     */
    public function flatten($prefix = '')
    {
        return $this->flattenArray($this->toArray(), $prefix);
    }

    /**
     * Method for recursively walking over our array, flattening it.
     * To trigger it, call $this->flatten()
     * @param $array
     * @param $prefix
     * @return array
     */
    private function flattenArray($array, $prefix)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flattenArray($value, $prefix . $key . '-');
            } else {
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
    private function unFlattenArray($array)
    {
        $result = [];

        foreach ($array as $key => $value) {
            $keys = explode('-', $key);

            $position = &$result;

            foreach ($keys as $index) {
                $position = &$position[$index];
            }

            $position = $value;
        }

        return $result;
    }

    // Hydrator methods

    /**
     * Populates the concrete class' properties with the array.
     *
     * @param array $data
     * @return self
     */
    public function populate(array $data)
    {
        // Foreach each passed property...
        foreach ($data as $k => $v) {
            // Only include known properties during the import...
            if (property_exists($this, $k) && !is_null($v)) {
                $this->set($k, $v);
            }
        }

        return $this;
    }

    /**
     * Populates the concrete class' properties with the passed flat array.
     *
     * @param array $data
     * @return self
     */
    public function populateWithFlatArray(array $data)
    {
        $data = $this->unFlattenArray($data);

        return $this->populate($data);
    }

    /**
     * Basic mapper. This should be overridden in the concrete class if needed.
     * This is included here to ensure the method is always available
     * and - by default - returns the original value it was passed.
     *
     * @param $property string The property name.
     * @param $value mixed The value we've been passed.
     * @return mixed The potentially updated value.
     */
    protected function map(/** @noinspection PhpUnusedParameterInspection */$property, $value)
    {
        return $value;
    }
}
