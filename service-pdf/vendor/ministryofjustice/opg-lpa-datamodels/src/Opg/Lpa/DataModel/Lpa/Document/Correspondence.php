<?php

namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Common\PhoneNumber;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback as CallbackConstraintSymfony;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents the person with whom Correspondence relating to the LPA should be sent.
 *
 * Class Correspondence
 * @package Opg\Lpa\DataModel\Lpa\Document
 */
class Correspondence extends AbstractData
{
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('who', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Choice([
                'choices' => [
                    self::WHO_DONOR,
                    self::WHO_ATTORNEY,
                    self::WHO_CERTIFICATE_PROVIDER,
                    self::WHO_OTHER
                ]
            ]),
        ]);

        $metadata->addPropertyConstraints('name', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\LongName'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('company', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'min' => 1,
                'max' => 75
            ]),
        ]);

        // We required either a name OR company to be set for a Correspondent to be considered valid.
        $metadata->addConstraint(new CallbackConstraintSymfony(function ($object, ExecutionContextInterface $context) {
            if (empty($object->name) && empty($object->company)) {
                $context->buildViolation((new Assert\NotNull())->message)->atPath('name/company')->addViolation();
            }
        }));

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\Address'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('email', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\EmailAddress'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('phone', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\PhoneNumber'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('contactByPost', [
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);

        $metadata->addPropertyConstraints('contactInWelsh', [
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map($property, $v)
    {
        switch ($property) {
            case 'name':
                if ($v instanceof Name) {
                    return new LongName($v->toArray());
                }
                return ($v instanceof LongName ? $v : new LongName($v));
            case 'address':
                return ($v instanceof Address ? $v : new Address($v));
            case 'email':
                return ($v instanceof EmailAddress ? $v : new EmailAddress($v));
            case 'phone':
                return ($v instanceof PhoneNumber ? $v : new PhoneNumber($v));
        }

        return parent::map($property, $v);
    }
}
