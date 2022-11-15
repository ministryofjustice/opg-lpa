<?php

namespace MakeShared\DataModel\Lpa\Document;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Common\PhoneNumber;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback as CallbackConstraintSymfony;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents the person with whom Correspondence relating to the LPA should be sent.
 *
 * Class Correspondence
 * @package MakeShared\DataModel\Lpa\Document
 */
class Correspondence extends AbstractData
{
    /**
     * Field length constants
     */
    const COMPANY_MIN_LENGTH = 1;
    const COMPANY_MAX_LENGTH = 75;

    const WHO_DONOR = 'donor';
    const WHO_ATTORNEY = 'attorney';
    const WHO_CERTIFICATE_PROVIDER = 'certificateProvider';
    const WHO_OTHER = 'other';

    /**
     * @var string The person's role within this LPA.
     */
    protected $who;

    /**
     * @var LongName Their name.
     */
    protected $name;

    /**
     * @var string Their company name.
     */
    protected $company;

    /**
     * @var Address Their postal address.
     */
    protected $address;

    /**
     * If this is set, we can contact them by email.
     *
     * @var EmailAddress Their email address.
     */
    protected $email;

    /**
     * If this is set, we can contact them by phone.
     *
     * @var PhoneNumber Their phone number.
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

    /**
     * @var bool Set to true if any default values have been manually overridden
     */
    protected $contactDetailsEnteredManually;

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
            case 'email':
                return ($value instanceof EmailAddress ? $value : new EmailAddress($value));
            case 'phone':
                return ($value instanceof PhoneNumber ? $value : new PhoneNumber($value));
        }

        return parent::map($property, $value);
    }
}
