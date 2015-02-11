<?php
namespace Opg\Lpa\DataModel\User;

use Opg\Lpa\DataModel\AbstractData;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;

/**
 * Represents a person's name.
 *
 * Class Name
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Name extends AbstractData {

    /**
     * @var string A person's title. E.g. Mr, Miss, Mrs, etc.
     */
    protected $title;

    /**
     * @var string A person's first name (or names).
     */
    protected $first;

    /**
     * @var string A person's last name.
     */
    protected $last;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('title', [
            // Can be null
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'min' => 1, 'max' => 5 ]),
        ]);

        $metadata->addPropertyConstraints('first', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'max' => 50 ]),
        ]);

        $metadata->addPropertyConstraints('last', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'max' => 50 ]),
        ]);

    } // function

    /**
     * Returns a string representation of the name.
     *
     * @return string
     */
    public function __toString(){

        $name  = ( !empty($this->title) ) ? "{$this->title} " : '';
        $name .= ( !empty($this->first) ) ? "{$this->first} " : '';
        $name .= ( !empty($this->last) )  ? "{$this->last}"    : '';

        // Tidy the string up...
        $name = trim($name);

        return $name;

    } // function

} // class
