<?php

namespace MakeShared\DataModel\Lpa\Document;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents the Donor associated with the LPA.
 *
 * Class Donor
 * @package MakeShared\DataModel\Lpa\Document
 */
class Donor extends AbstractData
{
    /**
     * Field length constants
     */
    const OTHER_NAMES_MIN_LENGTH = 1;
    const OTHER_NAMES_MAX_LENGTH = 50;

    /**
     * @var LongName Their name.
     */
    protected $name;

    /**
     * @var string Any other/past names they've may be known as.
     */
    protected $otherNames;

    /**
     * @var Address Their postal address.
     */
    protected $address;

    /**
     * @var Dob Their date of birth.
     */
    protected $dob;

    /**
     * @var EmailAddress Their email address.
     */
    protected $email;

    /**
     * @var bool Can the donor sign the form themselves.
     */
    protected $canSign;

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
     *
     * @return mixed Mapped value.
     */
    protected function map($property, $value)
    {
        switch ($property) {
            case 'name':
                return ($value instanceof LongName ? $value : new LongName($value));
            case 'address':
                return ($value instanceof Address ? $value : new Address($value));
            case 'dob':
                return (($value instanceof Dob || is_null($value)) ? $value : new Dob($value));
            case 'email':
                return (($value instanceof EmailAddress || is_null($value)) ? $value : new EmailAddress($value));
        }

        return parent::map($property, $value);
    }
}
