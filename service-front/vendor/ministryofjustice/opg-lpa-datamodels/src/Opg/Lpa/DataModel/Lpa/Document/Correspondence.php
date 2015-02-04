<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;

/**
 * Represents the person with whom Correspondence relating to the LPA should be sent.
 *
 * Class Correspondence
 * @package Opg\Lpa\DataModel\Lpa\Document
 */
class Correspondence extends AbstractData {

    const WHO_DONOR = 'donor';
    const WHO_ATTORNEY = 'attorney';
    const WHO_OTHER = 'other';

    /**
     * @var string The person's role within this LPA.
     */
    protected $who;

    /**
     * @var Elements\Name Their name.
     */
    protected $name;

    /**
     * @var string Their company name.
     */
    protected $company;

    /**
     * @var Elements\Address Their postal address.
     */
    protected $address;

    /**
     * If this is set, we can contact them by email.
     *
     * @var Elements\EmailAddress Their email address.
     */
    protected $email;

    /**
     * If this is set, we can contact them by phone.
     *
     * @var Elements\PhoneNumber Their phone number.
     */
    protected $phone;

    /**
     * @var bool Should we contact them by post.
     */
    protected $contactByPost;

    /**
     * @var bool Should we contact them in Welsh.
     */
    protected $contactInWelsh;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('who', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Choice([ 'choices' => [ self::WHO_DONOR, self::WHO_ATTORNEY, self::WHO_OTHER ] ]),
        ]);

        $metadata->addPropertyConstraints('name', [
            // Can be null
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Elements\Name' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('company', [
            // Can be null
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'min' => 1, 'max' => 75 ]),
        ]);

        // We required either a name OR company to be set for a Correspondent to be considered valid.
        $metadata->addConstraint( new Assert\Callback(function ($object, ExecutionContextInterface $context){
            if( empty($object->name) && empty($object->company) ){
                $context->buildViolation( (new Assert\NotNull())->message )->atPath('name/company')->addViolation();
            }
        }));

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Elements\Address' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('email', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Elements\EmailAddress' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('phone', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('contactByPost', [
            new Assert\Type([ 'type' => 'bool' ]),
        ]);

        $metadata->addPropertyConstraints('contactInWelsh', [
            new Assert\Type([ 'type' => 'bool' ]),
        ]);

    } // function

    //------------------------------------------------

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map( $property, $v ){

        switch( $property ){
            case 'name':
                return ($v instanceof Elements\Name) ? $v : new Elements\Name( $v );
            case 'address':
                return ($v instanceof Elements\Address) ? $v : new Elements\Address( $v );
            case 'email':
                return ($v instanceof Elements\EmailAddress) ? $v : new Elements\EmailAddress( $v );
            case 'phone':
                return ($v instanceof Elements\PhoneNumber) ? $v : new Elements\PhoneNumber( $v );
        }

        // else...
        return parent::map( $property, $v );

    } // function


} // class
