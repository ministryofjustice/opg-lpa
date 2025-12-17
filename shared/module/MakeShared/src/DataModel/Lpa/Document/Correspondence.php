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
    private const COMPANY_MIN_LENGTH = 0;
    private const COMPANY_MAX_LENGTH = 75;

    public const WHO_DONOR = 'donor';
    public const WHO_ATTORNEY = 'attorney';
    public const WHO_CERTIFICATE_PROVIDER = 'certificateProvider';
    public const WHO_OTHER = 'other';

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
            new Assert\NotBlank(),
            new Assert\Type('string'),
            new Assert\Choice(
                choices: [
                    self::WHO_DONOR,
                    self::WHO_ATTORNEY,
                    self::WHO_CERTIFICATE_PROVIDER,
                    self::WHO_OTHER
                ]
            ),
        ]);

        $metadata->addPropertyConstraints('name', [
            new Assert\Type('\MakeShared\DataModel\Common\LongName'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('company', [
            new Assert\Type('string'),
            new Assert\Length(
                min: self::COMPANY_MIN_LENGTH,
                max: self::COMPANY_MAX_LENGTH,
            ),
        ]);

        // We required either a name OR company to be set for a Correspondent to be considered valid.
        $metadata->addConstraint(new CallbackConstraintSymfony(function ($object, ExecutionContextInterface $context) {
            if (empty($object->name) && empty($object->company)) {
                $context->buildViolation((new Assert\NotNull())->message)->atPath('name/company')->addViolation();
            }
        }));

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank(),
            new Assert\Type('\MakeShared\DataModel\Common\Address'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('email', [
            new Assert\Type('\MakeShared\DataModel\Common\EmailAddress'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('phone', [
            new Assert\Type('\MakeShared\DataModel\Common\PhoneNumber'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('contactByPost', [
            new Assert\Type('bool'),
        ]);

        $metadata->addPropertyConstraints('contactInWelsh', [
            new Assert\Type('bool'),
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
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

    /**
     * @return string
     */
    public function getWho(): string
    {
        return $this->who;
    }

    /**
     * @param string $who
     * @return $this
     */
    public function setWho(string $who): Correspondence
    {
        $this->who = $who;

        return $this;
    }

    /**
     * @return LongName
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param LongName $name
     * @return $this
     */
    public function setName($name): Correspondence
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     * @return $this
     */
    public function setCompany($company): Correspondence
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     * @return $this
     */
    public function setAddress(Address $address): Correspondence
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return EmailAddress
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param EmailAddress $email
     * @return $this
     */
    public function setEmail($email): Correspondence
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return PhoneNumber
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param PhoneNumber $phone
     * @return $this
     */
    public function setPhone($phone): Correspondence
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return bool
     */
    public function isContactByPost()
    {
        return $this->contactByPost;
    }

    /**
     * @param bool $contactByPost
     * @return $this
     */
    public function setContactByPost($contactByPost): Correspondence
    {
        $this->contactByPost = $contactByPost;

        return $this;
    }

    /**
     * @return bool
     */
    public function isContactInWelsh()
    {
        return $this->contactInWelsh;
    }

    /**
     * @param bool $contactInWelsh
     * @return $this
     */
    public function setContactInWelsh($contactInWelsh): Correspondence
    {
        $this->contactInWelsh = $contactInWelsh;

        return $this;
    }

    /**
     * @return bool
     */
    public function isContactDetailsEnteredManually()
    {
        return $this->contactDetailsEnteredManually;
    }

    /**
     * @param bool $contactDetailsEnteredManually
     * @return $this
     */
    public function setContactDetailsEnteredManually($contactDetailsEnteredManually): Correspondence
    {
        $this->contactDetailsEnteredManually = $contactDetailsEnteredManually;

        return $this;
    }
}
