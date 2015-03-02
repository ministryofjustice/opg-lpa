<?php
namespace Opg\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\AbstractData;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;

/**
 * Represents a full LPA document, plus associated metadata.
 *
 * Class Lpa
 * @package Opg\Lpa\DataModel\Lpa
 */
class Lpa extends AbstractData implements CompleteInterface {

    /**
     * @var int The LPA identifier.
     */
    protected $id;

    /**
     * @var \DateTime the LPA was created.
     */
    protected $createdAt;

    /**
     * @var \DateTime the LPA was last updated.
     */
    protected $updatedAt;

    /**
     * @var \DateTime|null When the LPA was last updated AND it's status (at the moment in time) was complete.
     */
    protected $completedAt;

    /**
     * @var \DateTime|null DateTime the LPA was locked.
     */
    protected $lockedAt;

    /**
     * @var string LPA's owner User identifier.
     */
    protected $user;

    /**
     * @var Payment status.
     */
    protected $payment;

    /**
     * @var bool Flag to record whether the 'Who Are You' question has been answered with regards to this LPA.
     */
    protected $whoAreYouAnswered;

    /**
     * @var bool Is this LPA locked. i.e. read-only.
     */
    protected $locked;

    /**
     * @var int Reference to another LPA on which this LPA is based.
     */
    protected $seed;

    /**
     * @var int|null If this is a repeat LPA application, the relevant case number.
     */
    protected $repeatCaseNumber;

    /**
     * @var Document All the details making up the LPA document.
     */
    protected $document;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('id', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => 'int' ]),
            new Assert\Range([ 'min' => 0, 'max' => 99999999999 ]),
        ]);

        $metadata->addPropertyConstraints('createdAt', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('updatedAt', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('completedAt', [
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('lockedAt', [
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('user', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => 'xdigit' ]),
            new Assert\Length([ 'min' => 32, 'max' => 32 ]),
        ]);

        $metadata->addPropertyConstraints('payment', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Payment\Payment' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('whoAreYouAnswered', [
            new Assert\NotNull,
            new Assert\Type([ 'type' => 'bool' ]),
        ]);

        $metadata->addPropertyConstraints('locked', [
            new Assert\NotNull,
            new Assert\Type([ 'type' => 'bool' ]),
        ]);

        $metadata->addPropertyConstraints('seed', [
            new Assert\Type([ 'type' => 'int' ]),
            new Assert\Range([ 'min' => 0, 'max' => 99999999999 ]),
        ]);

        $metadata->addPropertyConstraints('repeatCaseNumber', [
            new Assert\Type([ 'type' => 'int' ]),
        ]);

        $metadata->addPropertyConstraints('document', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\Document' ]),
            new Assert\Valid,
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
            case 'updatedAt':
            case 'createdAt':
            case 'completedAt':
            case 'lockedAt':
                return ($v instanceof \DateTime || is_null($v)) ? $v : new \DateTime( $v );
            case 'payment':
                return ($v instanceof Payment || is_null($v)) ? $v : new Payment( $v );
            case 'document':
                return ($v instanceof Document || is_null($v)) ? $v : new Document( $v );
        }

        // else...
        return parent::map( $property, $v );

    } // function

    //------------------------------------------------

    /**
     * Returns $this as an array suitable for inserting into MongoDB.
     *
     * @return array
     */
    public function toMongoArray(){
        $data = parent::toMongoArray();

        // Rename 'id' to '_id' (keeping it at the beginning of the array)
        $data = [ '_id'=>$data['id'] ] + $data;

        unset($data['id']);

        return $data;
    }

    /**
     * Check whether the LPA document is complete and valid at the business level.
     *
     * @return bool
     */
    public function isComplete(){

        return true;

    } // function

    //------------------------------------------------

    /**
     * Return an abbreviated (summary) version of the LPA.
     *
     * @return array
     */
    public function abbreviatedToArray(){

        $data = $this->toArray();

        // Include these top level fields...
        $data = array_intersect_key( $data, array_flip([
            'id', 'lockedAt', 'updatedAt', 'createdAt', 'user', 'locked', 'document'
        ]));

        // Include these document level fields...
        $data['document'] = array_intersect_key( $data['document'], array_flip([
            'donor', 'type'
        ]));

        return $data;

    } // function

} // class
